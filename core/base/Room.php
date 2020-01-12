<?php

namespace Games\Core;

use Games\Core\Controller;
use Games\Core\Player;
use Games\Utils\Mapping;
use Games\Utils\Mapable;
use Games\Utils\Comparable;

use Workerman\Lib\Timer;
use Monolog\Logger;

abstract class Room implements Mapable
{
    protected $id;
    protected $status;
    protected $numplayers;

    protected $ready;
    protected $players;
    protected $spectators;

    protected $controller;
    protected $logger;

    const READY_TIME = 10;
    const STATUS_EMPTY = 0;
    const STATUS_WAITING = 1;
    const STATUS_READY = 2;
    const STATUS_PLAYING = 3;

    public function __construct($id, Controller $controller, Logger $logger)
    {
        $this->id = $id;
        $this->status = self::STATUS_EMPTY;

        $this->players = new Mapping();
        $this->spectators = new Mapping();

        $this->controller = $controller;
        $this->logger = $logger;
    }

    public function equals(Comparable $object){
        return get_class($this) === get_class($object) && $this->id === $object->getId();
    }

    public function getId(){
        return $this->id;
    }

    /**
     * Helpers
     */
    public function isEmpty(){
        return $this->status === self::STATUS_EMPTY;
    }

    public function isWaiting(){
        return $this->status === self::STATUS_WAITING;
    }

    public function isReady(){
        return $this->status === self::STATUS_READY;
    }

    public function isPlaying(){
        return $this->status === self::STATUS_PLAYING;
    }

    public function emit($event, $data = null){
        if ($data === null) {
            $this->controller->getIO()->to("r" . $this->id)->emit($event);
        }else{
            $this->controller->getIO()->to("r" . $this->id)->emit($event, $data);
        }
    }

    /**
     * Room event handlers
     */
    public function join(Player $player)
    {
        if($this->isReady() || $this->isPlaying()){
            return false;
        }

        if($this->isWaiting() && sizeof($this->players) >= $this->numplayers){
            return false;
        }

        if($this->players->contains($player)){
            print "WTF2";
            return false;
        }

        $this->players->add($player);
        $player->getSocket()->join("r" . $this->id);

        if (sizeof($this->players) == $this->numplayers) {
            $this->setStatus(self::STATUS_READY);
        } else {
            $this->setStatus(self::STATUS_WAITING);
        }

        return true;
    }

    public function leave(Player $player)
    {
        if($this->players->contains($player)){
            $this->players->remove($player);
            $player->getSocket()->leave("r" . $this->id);

            if($this->isPlaying() && sizeof($this->players) == 0){
                $this->setStatus(self::STATUS_EMPTY);
            }else if($this->isReady() && sizeof($this->players) < $this->numplayers){
                $this->setStatus(self::STATUS_WAITING);
                $this->emit("unready");
            }else if(sizeof($this->players) > 0){
                $this->setStatus(self::STATUS_WAITING);
            } else {
                $this->setStatus(self::STATUS_EMPTY);
            }

            return true;
        }

        if($this->spectators->contains($player)){
            $this->spectators->remove($player);
            $player->getSocket()->leave("r" . $this->id);
            return true;
        }

        return false;
    }

    public function spectate(Player $player)
    {
        if(!$this->isPlaying()){
            return false;
        }

        if($this->spectators->contains($player)){
            return false;
        }
        
        $this->spectators->add($player);
        $player->getSocket()->join("r" . $this->id);

        return true;
    }

    public function playerReady(Player $player)
    {
        if($this->isReady()){
            if(!$this->ready->contains($player)){
                $this->ready->add($player);

                if(sizeof($this->ready) == $this->numplayers){
                    $this->setStatus(self::STATUS_PLAYING);
                }
            }
        }
    }

    public function playerUnready(Player $player)
    {
        if($this->isReady()){
            $this->controller->onRoomLeave($player->getSocket());
        }
    }

    public function playerMessage(Player $player, $message){
        $this->emit("playerMessage", [
            "chat" => "room",
            "msg" => $message,
            "playerid" => $player->getId()
        ]);
    }

    /**
     * Room status management
     */
    protected function setStatus($status)
    {
        if ($this->status !== $status) {
            $previous = $this->status;
            $this->status = $status;
            $this->onStatusChange($previous);
        }
    }

    protected function onStatusChange($previous)
    {
        $comb = $previous . "x" . $this->status;

        switch ($comb) {
            // Empty
            case self::STATUS_EMPTY . "x" . self::STATUS_WAITING:
                $this->configure();
                break;
            case self::STATUS_EMPTY . "x" . self::STATUS_READY:
                $this->logger->error(__FUNCTION__.":".__LINE__ .": Illegal Status ". $comb ." in ". $this);
                break;
            case self::STATUS_EMPTY . "x" . self::STATUS_PLAYING:
                $this->logger->error(__FUNCTION__.":".__LINE__ .": Illegal Status ". $comb ." in ". $this);
                break;

            // Waiting
            case self::STATUS_WAITING . "x" . self::STATUS_EMPTY:
                break;
            case self::STATUS_WAITING . "x" . self::STATUS_READY:
                $this->prepare();
                break;
            case self::STATUS_WAITING . "x" . self::STATUS_PLAYING:
                $this->logger->error(__FUNCTION__.":".__LINE__ .": Illegal Status ". $comb ." in ". $this);
                break;

            // Ready
            case self::STATUS_READY . "x" . self::STATUS_EMPTY:
                $this->logger->error(__FUNCTION__.":".__LINE__ .": Illegal Status ". $comb ." in ". $this);
                break;
            case self::STATUS_READY . "x" . self::STATUS_WAITING:
                break;
            case self::STATUS_READY . "x" . self::STATUS_PLAYING:
                $this->start();
                break;

            // Playing
            case self::STATUS_PLAYING . "x" . self::STATUS_EMPTY:
                $this->logger->error(__FUNCTION__.":".__LINE__ .": Illegal Status ". $comb ." in ". $this);
                break;
            case self::STATUS_PLAYING . "x" . self::STATUS_WAITING:
                $this->logger->error(__FUNCTION__.":".__LINE__ .": Illegal Status ". $comb ." in ". $this);
                break;
            case self::STATUS_PLAYING . "x" . self::STATUS_READY:
                $this->logger->error(__FUNCTION__.":".__LINE__ .": Illegal Status ". $comb ." in ". $this);
                break;

            default:
                $this->logger->error(__FUNCTION__.":".__LINE__ .": Undefined Status ". $comb ." in ". $this);
        }
    }

    /**
     * Game management
     */
    
    abstract public function onPlayerAction($player, $data);

    abstract protected function configure();

    protected function prepare()
    {
        $this->ready = new Mapping();
        $this->emit("ready", self::READY_TIME);
                
        //add($time_interval, $func, $args = array(), $persistent = true)
        Timer::add(self::READY_TIME, function () {
            if($this->isReady()){
                $this->emit("unready");

                foreach($this->players as $player){
                    if(!$this->ready->contains($player)){
                        $this->controller->onRoomLeave($player->getSocket());
                    }
                }
            }
        }, array(), false);
    }

    abstract protected function start();

    abstract protected function finish();

    public function jsonSerialize(){
        return [
            "id" => $this->id,
            "players" => $this->players->keys(),
            "spectators" => $this->spectators->keys(),
            "status" => $this->status,
            "numplayers" => $this->numplayers
        ];
    }
    
    abstract public function gameSerialize();

    public function __toString()
    {
        return "#" . $this->id;
    }
}
