<?php

class Controller{
    private $io;
    private $rooms;
    private $players;

    private $next_id;

    public function __construct($io){
        $this->io = $io;
        $this->players = array();
        $this->rooms = array();

        $this->next_id = 0;

        for($i = 1; $i <= PARCHIS_ROOMS; $i++){
            $this->rooms[$i] = new Room($i);
        }
    }

    public function onConnect(&$socket, $data){
        if(!isset($data['username'])){
            $this->debug("CONNECT:ERROR: No username");
            return;
        }

        // @TODO: Auth / unique nickname

        $player = new Player(++$this->next_id, $username);
        $client->player = $player;
        
        $this->debug("CONNECT:SUCCESS: ". $socket->player->id .":". $socket->player->username);
        $io->emit('user_login', $player);
        $socket->emit("data", $this->serialize());
    }

    public function onDisconnect(&$socket){
        if(!isset($socket->player) || !in_array($socket->player, $this->players)){
            $this->debug("DISCONNECT:ERROR:1: Socket had no player");
            return;
        }

        if($socket->player->room != null){
            $this->onLeaveRoom($socket->player);
        }

        $this->debug("DISCONNECT:SUCCESS: ". $socket->player->id .":". $socket->player->username);
        $io->emit('user_logout', $socket->player);
        unset($this->players[$socket->player->id]);
    }

    public function onMessage(&$socket, $data){
        if(!isset($data['chat']) || !isset($data['msg']) || $data['msg'] == ""){
            $this->debug("CHAT:ERROR: Wrong formatted message");
            return;
        }

        if($data['chat'] == "global"){
            $this->debug("CHAT:SUCCESS: ". $socket->player->id .":". $socket->player->username .":global: " . $data['msg']);
            $io->emit('message', array(
                'chat' => $data['chat'],
                'username'=> $socket->player->username,
                'msg' => $data['msg']
            ));
        }else{
            // @TODO: Implement room chat
            $this->debug("CHAT:ERROR:ROOM Not implemented ". $socket->player->id .":". $socket->player->username, $data);
        }

    }

    public function onLeaveRoom($player){

    }



    public function get_room($room_id){
        if(!isset($this->rooms[$room_id]))
            return false;
        return $this->rooms[$room_id];
    }
    
    public function join_room($player, $room_id){
        if(!isset($this->rooms[$room_id]))
            return false;
        return $this->rooms[$room_id]->join($player);
    }

    public function leave_room($player){
        if(!isset($this->rooms[$player->room]))
            return false;
        return $this->rooms[$player->room]->leave($player) || 
            $this->rooms[$player->room]->leave_spectate($player);
    }

    public function join_room_spectator($player, $room_id){
        if(!isset($this->rooms[$room_id]))
            return false;
        return $this->rooms[$room_id]->join_spectate($player);
    }



    public function debug($msg, $data = null){
        echo $msg . "\n";
        if($data !== null)
            echo "\t" . print_r($data, true) . "\n";
    }

    public function serialize(){
        return array(
            "players" => $this->players,
            "rooms" => $this->rooms
        );
    }
}




class Room{
    public $id;
    public $status;
    public $mode;
    public $max_players;
    public $players;
    public $spectators;

    public function __construct($id){
        $this->id = $id;
        $this->status = ROOM_STATUS_EMPTY;
        $this->mode = ROOM_MODE_INDIVIDUAL;
        $this->max_players = 4;
        $this->players = array();
        $this->spectators = array();
    }

    public function join($player){
        if($this->status != ROOM_STATUS_EMPTY && $this->status != ROOM_STATUS_WAITING)
            return false;

        if($this->max_players <= sizeof($this->players))
            return false;

        $this->players[] = $player;
        $player->room = $this->id;
        return true;
    }

    public function leave($player){
        $index = array_search($player, $this->players);
        if($index === false){
            return false;
        }
        unset($this->players[$index]);
        $player->room = null;
        return true;
    }

    public function join_spectate($player){
        $this->spectators[] = $player;
        $player->room = $this->id;
        return true;
    }

    public function leave_spectate($player){
        $index = array_search($player, $this->spectators);
        if($index === false){
            return false;
        }
        unset($this->spectators[$index]);
        $player->room = null;
        return true;
    }
}

class Player{
    public $id;
    public $username;
    public $room;

    public function __construct($id, $username){
        $this->id = $id;
        $this->username = $username;
        $this->room = null;
    }
}