<?php

namespace Games\Core;

use Games\Core\Room;
use Games\Core\Player;

class Controller{
    private $io;
    private $logger;

    private $roomClass;
    private $playerClass;

    private $rooms;
    private $players;

    private $next_id;

    public function __construct($io, $logger, $roomClass, $playerClass){
        $this->io = $io;
        $this->logger = $logger;
        $this->roomClass = $roomClass;
        $this->playerClass = $playerClass;

        $this->players = array();
        $this->rooms = array();

        $this->next_id = 0;

        for($i = 1; $i <= PARCHIS_ROOMS; $i++){
            $this->rooms[$i] = new $this->roomClass($i, $this, $logger);
        }
    }

    public function onConnect($socket, $data){
        if(!isset($data['username'])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->conn->remoteAddress .": No username");
            return;
        }

        if(isset($socket->player)){
            $this->onDisconnect($socket);
        }

        // @TODO: Auth / unique nickname
        $socket->player = new $this->playerClass(++$this->next_id, $data['username'], $socket);
        $this->players[$socket->player->id] = $socket->player;
        
        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->conn->remoteAddress .": ". $socket->player);
        $this->io->emit('user_login', $socket->player);
        $socket->emit("data", array(
            "you" => $socket->player->id,
            "players" => $this->players,
            "rooms" => $this->rooms
        ));
    }

    public function onDisconnect($socket){
        if(!isset($socket->player) || !isset($this->players[$socket->player->id])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->conn->remoteAddress .": Socket had no player");
            return;
        }

        if($socket->player->room != null){
            $this->onRoomLeave($socket);
        }

        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->conn->remoteAddress .": ". $socket->player);
        $this->io->emit('user_logout', $socket->player);
        unset($this->players[$socket->player->id]);
    }

    public function onMessage($socket, $data){
        if(!isset($data['chat']) || !isset($data['msg']) || $data['msg'] == ""){
            $this->logger->error(__FUNCTION__.":".__LINE__ .": Wrong formatted message");
            return;
        }

        if($data['chat'] == "global"){
            $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .":global: ". $data['msg']);
            $this->io->emit('message', array(
                'chat' => $data['chat'],
                'username'=> $socket->player->username,
                'msg' => $data['msg']
            ));
        }else if($data['chat'] == "room"){
            if($socket->player->room === null){
                $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .":room: Not in a room: " . $data['msg']);
                return;
            }
            if(!isset($this->rooms[$socket->player->room])){
                $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .":room: Room does not exist: " . $data['msg']);
                return;
            }

            $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .":room:".$socket->player->room." ". $data['msg']);
            $this->io->to("room" . $socket->player->room)->emit('message', array(
                'chat' => $data['chat'],
                'username'=> $socket->player->username,
                'msg' => $data['msg']
            ));
        }else{
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .":unknown: " . $data);
        }
    }

    public function onRoomLeave($socket){
        if($socket->player->room !== null){
            if(isset($this->rooms[$socket->player->room])){
                $this->rooms[$socket->player->room]->leave($socket->player);
                $this->updateRoom($this->rooms[$socket->player->room]);
                $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .": left the room " . $this->rooms[$socket->player->room]);
            }
            $socket->player->room = null;
            $this->updatePlayer($socket->player);
        }
    }

    public function onRoomJoin($socket, $data){
        if(!isset($data["room"])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": No room specified". print_r($data, true));
            return;
        }
        if(!isset($this->rooms[$data["room"]])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Invalid room ". print_r($data, true));
            return;
        }

        $this->onRoomLeave($socket);

        if($this->rooms[$data["room"]]->join($socket->player)){
            $socket->player->room = $this->rooms[$data["room"]]->id;
            $this->updateRoom($this->rooms[$data["room"]]);
            $this->updatePlayer($socket->player);
            $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .": joined the room " . $this->rooms[$data["room"]]);
        }else{
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": can't join ". $this->rooms[$data["room"]]);
        }
    }

    public function onRoomSpectate($socket, $data){
        if(!isset($data["room"])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": No room specified". print_r($data, true));
            return;
        }
        if(!isset($this->rooms[$data["room"]])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Invalid room ". print_r($data, true));
            return;
        }

        $this->onRoomLeave($socket);

        if($this->rooms[$data["room"]]->spectate($socket->player)){
            $socket->player->room = $this->rooms[$data["room"]]->id;
            $this->updateRoom($this->rooms[$data["room"]]);
            $this->updatePlayer($socket->player);
            $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .": is now spectating the room " . $this->rooms[$data["room"]]);
        }else{
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": can't spectate ". $this->rooms[$data["room"]]);
        }
    }

    public function onReady($socket){
        if(!isset($this->rooms[$socket->player->room])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Invalid room ". $socket->player->room);
            return;
        }
        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .":" . $this->rooms[$socket->player->room]);
        $this->rooms[$socket->player->room]->playerReady($socket->player);
    }

    public function onUnready($socket){
        if(!isset($this->rooms[$socket->player->room])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Invalid room ". $socket->player->room);
            return;
        }
        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .":" . $this->rooms[$socket->player->room]);
        $this->rooms[$socket->player->room]->playerUnready($socket->player);
    }
    
    public function onGameAction($socket, $data){
        if(!isset($this->rooms[$socket->player->room])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Invalid room ". $socket->player->room);
            return;
        }
        if(!isset($data["action"])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Unspecified action ". print_r($data, true));
            return;
        }
        if($this->rooms[$socket->player->room]->onPlayerAction($socket->player, $data) === false){
            $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .":" . $this->rooms[$socket->player->room] .": Bad action ". print_r($data, true));
        }
    }

    /* Async Room Events */
    public function roomJoin($id, $player){
        $player->getSocket()->join("room" . $id);
    }

    public function roomLeave($id, $player){
        $player->getSocket()->leave("room" . $id);
    }

    public function roomEmit($id, $event, $data = null){
        if(!isset($this->rooms[$id])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .": Invalid room ". $this->rooms[$id] ." for ". $event);
            return;
        }
        // $this->logger->info(__FUNCTION__.":".__LINE__ .":". $this->rooms[$id] .":".$event);
        if($data !== null)
            $this->io->to("room" . $id)->emit($event, $data);
        else
            $this->io->to("room" . $id)->emit($event);
    }


    /* Async Updates */
    public function updatePlayer($player){
        $this->io->emit("update_player", $player);
    }

    public function updateRoom($room){
        $this->io->emit("update_room", $room);
    }
}
