<?php

namespace Games\Core;

use Games\Core\Room;
use Games\Core\Player;
use Games\Utils\Mapping;

class Controller implements \JsonSerializable
{
    private $io;
    private $logger;

    private $roomClass;
    private $playerClass;

    private $rooms;
    private $players;

    private $next_id;

    public function __construct($io, $logger, $roomClass, $playerClass)
    {
        $this->io = $io;
        $this->logger = $logger;
        
        $this->roomClass = $roomClass;
        $this->playerClass = $playerClass;

        $this->players = new Mapping();
        $this->rooms = new Mapping();

        $this->next_id = 0;

        for ($i = 1; $i <= PARCHIS_ROOMS; $i++) {
            $this->rooms->add(new $this->roomClass($i, $this, $logger));
        }
    }

    /**
     * Getters
     */
    public function getIO(){
        return $this->io;
    }
    
    public function getLogger(){
        return $this->logger;
    }

    /**
     * Helpers
     */
    private function isPlayerConnected($socket){
        return isset($socket->player) && $this->players->contains($socket->player);
    }

    // Legacy
    public function roomEmit($room, $event, $data = null)
    {
        // $this->logger->info(__FUNCTION__.":".__LINE__ .":". $this->rooms[$id] .":".$event);
        if ($data !== null) {
            $this->io->to("room" . $room->getId())->emit($event, $data);
        } else {
            $this->io->to("room" . $room->getId())->emit($event);
        }
    }

    /**
     * Handlers
     */
    public function onConnect($socket, $data){
        if (!isset($data['username'])) {
            return;
        }
        
        // @TODO: Auth

        
        $socket->player = new $this->playerClass(++$this->next_id, $data['username'], $socket);
        $this->players->add($socket->player);

        $data = $this->jsonSerialize();
        $data["id"] = $socket->player->getId();

        
        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->conn->remoteAddress .": ". $socket->player);
        
        $this->io->to("secure")->emit("playerConnect", $socket->player->jsonSerialize());

        $socket->join("secure");
        $socket->emit("successAuth", $data);
    }

    public function onDisconnect($socket)
    {
        if(!$this->isPlayerConnected($socket)){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->conn->remoteAddress .": Socket had no player");
            return;
        }
        
        if($socket->player->getRoom() !== false){
            $this->onRoomLeave($socket);
        }

        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->conn->remoteAddress .": ". $socket->player);

        $socket->leave("secure");
        $this->io->to("secure")->emit("playerDisconnect", $socket->player->getId());
        
        $this->players->remove($socket->player);
    }

    public function onMessage($socket, $data){
        if(!$this->isPlayerConnected($socket)){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->conn->remoteAddress .": Socket had no player: " . print_r($data, true));
            return;
        }
        
        if (!isset($data['chat']) || !isset($data['msg'])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Wrong formatted message: " . print_r($data, true));
            return;
        }
        
        $chat = $data['chat'];
        $message = trim(strip_tags($data["msg"]));

        if(empty($message)){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Empty message");
            return;
        }

        if(strlen($message) > 256){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Message too long: ". strlen($message) ." characters");
            return;
        }

        switch($chat){
            case "global":
                $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .":global: ". $message);
                $this->io->to("secure")->emit("playerMessage", [
                    'chat' => $chat,
                    'msg'  => $message,
                    'playerid'=> $socket->player->getId()
                ]);
                break;

            case "room":
                $room = $socket->player->getRoom();
                if ($room === false) {
                    $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .":room: Not in a room: " . $message);
                    return;
                }

                $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .":room:".$socket->player->getRoom()." ". $message);
                
                $room->playerMessage($socket->player, $message);
                break;

            default:
                $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Invalid message: ". print_r($data, true));
                break;
        }
    }

    public function onRoomJoin($socket, $data)
    {
        if(!$this->isPlayerConnected($socket)){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->conn->remoteAddress .": Socket had no player:" . print_r($data, true));
            return;
        }

        if (!isset($data["room"])) {
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": No room specified: ". print_r($data, true));
            return;
        }

        $room = $this->rooms->get($data["room"]);
        if($room === false){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Invalid room: ". print_r($data, true));
            return;
        }

        $this->onRoomLeave($socket);

        if($room->join($socket->player)){
            $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .": joined the room " . $room);

            $socket->player->setRoom($room);

            $this->io->emit("playerJoinRoom", [
                "roomid" => $room->getId(),
                "playerid" => $socket->player->getId()
            ]);
        }else{
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": can't join ". $room);
        }
    }

    public function onRoomSpectate($socket, $data)
    {
        if(!$this->isPlayerConnected($socket)){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->conn->remoteAddress .": Socket had no player:" . print_r($data, true));
            return;
        }

        if (!isset($data["room"])) {
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": No room specified: ". print_r($data, true));
            return;
        }

        $room = $this->rooms->get($data["room"]);
        if($room === false){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Invalid room: ". print_r($data, true));
            return;
        }

        $this->onRoomLeave($socket);

        if($room->spectate($socket->player)){
            $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .": is now spectating the room " . $room);

            $socket->player->setRoom($room);

            $this->io->emit("playerSpectateRoom", [
                "roomid" => $room->getId(),
                "playerid" => $socket->player->getId()
            ]);
        }else{
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": can't spectate ". $room);
        }
    }
    
    public function onRoomLeave($socket)
    {
        if(!$this->isPlayerConnected($socket)){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->conn->remoteAddress .": Socket had no player");
            return;
        }
        
        $room = $socket->player->getRoom();
        if($room === false){
            return;
        }
        
        if($room->leave($socket->player)){
            $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .": left the room " . $room);

            $socket->player->unsetRoom();

            $this->io->emit("playerLeaveRoom", [
                "roomid" => $room->getId(),
                "playerid" => $socket->player->getId()
            ]);
        }else{
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": can't leave ". $room);
        }
    }

    public function onReady($socket)
    {
        if(!$this->isPlayerConnected($socket)){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->conn->remoteAddress .": Socket had no player");
            return;
        }

        $room = $socket->player->getRoom();
        if($room === false){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Not in a room");
            return;
        }

        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .":" . $room);
        $room->playerReady($socket->player);
    }

    public function onUnready($socket)
    {
        if(!$this->isPlayerConnected($socket)){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->conn->remoteAddress .": Socket had no player");
            return;
        }

        $room = $socket->player->getRoom();
        if($room === false){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Not in a room");
            return;
        }

        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .":" . $room);
        $room->playerUnready($socket->player);
    }

    public function onKick($socket, $data){
        if(!$this->isPlayerConnected($socket)){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->conn->remoteAddress .": Socket had no player");
            return;
        }

        $room = $socket->player->getRoom();
        if($room === false){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Not in a room");
            return;
        }

        if (!isset($data["playerid"])) {
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": No playerid specified: ". print_r($data, true));
            return;
        }

        $player = $this->players->get($data["playerid"]);
        if($player === false){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Player not found: ". $data["playerid"]);
            return;
        }

        if($room->playerKick($socket->player, $player)){
            $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player ." kicked ". $player ." of " . $room);
        }else{
            $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player ." can't kick ". $player ." of " . $room);
        }        
    }
    
    public function onGameAction($socket, $data)
    {
        if(!$this->isPlayerConnected($socket)){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->conn->remoteAddress .": Socket had no player");
            return;
        }

        $room = $socket->player->getRoom();
        if($room === false){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Not in a room");
            return;
        }

        if(!isset($data["action"])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Unspecified action ". print_r($data, true));
            return;
        }

        if($room->onPlayerAction($socket->player, $data) === false){
            $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .":" . $room .": Bad action ". print_r($data, true));
        }
    }

    
    public function jsonSerialize()
    {
        return [
            "players" => $this->players->jsonSerialize(),
            "rooms" => $this->rooms->jsonSerialize()
        ];
    }
}
