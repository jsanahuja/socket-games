<?php

namespace Games\Core;

use Games\Core\Controller;
use Workerman\Lib\Timer;
use Monolog\Logger;

abstract class Room{
    public $id;
    public $status;
    public $numplayers;

    private $ready;
    public $players;
    public $spectators;

    protected $controller;
    protected $logger;

    public function __construct($id, Controller $controller, Logger $logger){
        $this->id = $id;
        $this->status = ROOM_STATUS_EMPTY;

        $this->players = array();
        $this->spectators = array();

        $this->controller = $controller;
        $this->logger = $logger;
    }

    /**
     * Room configurations
     */
    public function setNumplayers($num){
        $this->numplayers = $num;
    }

    /**
     * Room event handlers
     */
    public function join($player){
        if($this->status != ROOM_STATUS_EMPTY && $this->status != ROOM_STATUS_WAITING)
            return false;

        if($this->status == ROOM_STATUS_WAITING && $this->numplayers <= sizeof($this->players))
            return false;

        $this->players[] = $player;
        $this->controller->roomJoin($this->id, $player);

        if(sizeof($this->players) == $this->numplayers)
            $this->setStatus(ROOM_STATUS_READY);
        else
            $this->setStatus(ROOM_STATUS_WAITING);

        return true;
    }

    public function leave($player){
        $index = array_search($player, $this->players);
        if($index !== false){
            unset($this->players[$index]);
            $this->controller->roomLeave($this->id, $player);
        }
        
        $index = array_search($player, $this->spectators);
        if($index !== false){
            unset($this->spectators[$index]);
        }

        if($this->status === ROOM_STATUS_PLAYING){
            if(sizeof($this->players) == 0){
                $this->setStatus(ROOM_STATUS_EMPTY);
            }
        }else if($this->status === ROOM_STATUS_READY){
            if(sizeof($this->players) < $this->numplayers){
                $this->setStatus(ROOM_STATUS_WAITING);
                $this->controller->roomEmit($this->id, "unready");
            }
        }else{
            if(sizeof($this->players) > 0)
                $this->setStatus(ROOM_STATUS_WAITING);
            else
                $this->setStatus(ROOM_STATUS_EMPTY);
        }
    }

    public function spectate($player){
        if($this->status !== ROOM_STATUS_PLAYING){
            return false;
        }

        $this->spectators[] = $player;
        return true;
    }

    public function playerReady($player){
        if($this->status === ROOM_STATUS_READY){
            if(!in_array($player, $this->ready)){
                $this->ready[] = $player;

                if(sizeof($this->ready) == $this->numplayers)
                    $this->setStatus(ROOM_STATUS_PLAYING);
            }
        }
    }

    public function playerUnready($player){
        if($this->status === ROOM_STATUS_READY){
            $this->controller->onRoomLeave($player->getSocket());
        }
    }

    /**
     * Room status management
     */
    protected function setStatus($status){
        if($this->status !== $status){
            $previous = $this->status;
            $this->status = $status;
            $this->onStatusChange($previous);
        }
    }

    protected function onStatusChange($previous){
        $comb = $previous . "x" . $this->status;

        switch($comb){
            // Empty
            case ROOM_STATUS_EMPTY . "x" . ROOM_STATUS_WAITING:
                $this->configure();
                break;
            case ROOM_STATUS_EMPTY . "x" . ROOM_STATUS_READY:
                $this->logger->error(__FUNCTION__.":".__LINE__ .": Illegal Status ". $comb ." in ". $this);
                break;
            case ROOM_STATUS_EMPTY . "x" . ROOM_STATUS_PLAYING:
                $this->logger->error(__FUNCTION__.":".__LINE__ .": Illegal Status ". $comb ." in ". $this);
                break;

            // Waiting
            case ROOM_STATUS_WAITING . "x" . ROOM_STATUS_EMPTY:
                break;
            case ROOM_STATUS_WAITING . "x" . ROOM_STATUS_READY:
                $this->prepare();
                break;
            case ROOM_STATUS_WAITING . "x" . ROOM_STATUS_PLAYING:
                $this->logger->error(__FUNCTION__.":".__LINE__ .": Illegal Status ". $comb ." in ". $this);
                break;

            // Ready
            case ROOM_STATUS_READY . "x" . ROOM_STATUS_EMPTY:
                $this->logger->error(__FUNCTION__.":".__LINE__ .": Illegal Status ". $comb ." in ". $this);
                break;
            case ROOM_STATUS_READY . "x" . ROOM_STATUS_WAITING:
                break;
            case ROOM_STATUS_READY . "x" . ROOM_STATUS_PLAYING:
                $this->start();
                break;

            // Playing
            case ROOM_STATUS_PLAYING . "x" . ROOM_STATUS_EMPTY:
                $this->logger->error(__FUNCTION__.":".__LINE__ .": Illegal Status ". $comb ." in ". $this);
                break;
            case ROOM_STATUS_PLAYING . "x" . ROOM_STATUS_WAITING:
                $this->logger->error(__FUNCTION__.":".__LINE__ .": Illegal Status ". $comb ." in ". $this);
                break;
            case ROOM_STATUS_PLAYING . "x" . ROOM_STATUS_READY:
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

    protected function prepare(){
        $this->ready = array();
        $this->controller->roomEmit($this->id, "ready");
                
        //add($time_interval, $func, $args = array(), $persistent = true)
        Timer::add(GAME_READY_TIME, function() {
            if($this->status === ROOM_STATUS_READY){
                $this->controller->roomEmit($this->id, "unready");
                foreach($this->players as $player){
                    if(!in_array($player, $this->ready)){
                        $this->controller->onRoomLeave($player->getSocket());
                    }
                }
            }
        }, array(), false);
    }

    abstract protected function start();

    abstract protected function finish();

    public function __toString(){
        return "#" . $this->id;
    }
}
