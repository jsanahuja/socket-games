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

        if(isset($socket->player)){
            $this->onDisconnect($socket);
        }

        // @TODO: Auth / unique nickname
        $socket->player = new Player(++$this->next_id, $data['username']);
        $this->players[$socket->player->id] = $socket->player;
        
        $this->debug("CONNECT:SUCCESS: ". $socket->player->id .":". $socket->player->username);
        $this->io->emit('user_login', $socket->player);
        $socket->emit("data", $this->serialize());
    }

    public function onDisconnect(&$socket){
        if(!isset($socket->player) || !isset($this->players[$socket->player->id])){
            $this->debug("DISCONNECT:ERROR:1: Socket had no player");
            return;
        }

        if($socket->player->room != null){
            $this->onLeaveRoom($socket);
        }

        $this->debug("DISCONNECT:SUCCESS: ". $socket->player->id .":". $socket->player->username);
        $this->io->emit('user_logout', $socket->player);
        unset($this->players[$socket->player->id]);
    }

    public function onMessage(&$socket, $data){
        if(!isset($data['chat']) || !isset($data['msg']) || $data['msg'] == ""){
            $this->debug("CHAT:ERROR: Wrong formatted message");
            return;
        }

        if($data['chat'] == "global"){
            $this->debug("CHAT:SUCCESS: ". $socket->player->id .":". $socket->player->username .":global: " . $data['msg']);
            $this->io->emit('message', array(
                'chat' => $data['chat'],
                'username'=> $socket->player->username,
                'msg' => $data['msg']
            ));
        }else{
            if($socket->player->room === null){
                $this->debug("CHAT:ERROR: ". $socket->player->id .":". $socket->player->username .":room: Not in a room: " . $data['msg']);
            }
            if(!isset($this->rooms[$socket->player->room])){
                $this->debug("CHAT:ERROR: ". $socket->player->id .":". $socket->player->username .":room: Room does not exist: " . $data['msg']);
            }

            // foreach($this->rooms[$socket->player->room]->players as $player){

            // }

            // $this->rooms
            // @TODO: Implement room chat
            $this->debug("CHAT:ERROR:ROOM Not implemented ". $socket->player->id .":". $socket->player->username, $data);
        }

    }

    public function onLeaveRoom(&$socket){
        if($socket->player->room !== null){
            if(isset($this->rooms[$socket->player->room])){
                $this->rooms[$socket->player->room]->leave($socket->player);
                $this->io->emit("update_room", $this->rooms[$socket->player->room]);
                
                $this->debug("ROOM:LEAVE:SUCCESS: Player ". $socket->player->username ." left room #". $this->rooms[$socket->player->room]->id);
            }
            $socket->player->room = null;
            $this->io->emit("update_player", $socket->player);
        }
    }

    public function onRoomJoin(&$socket, $data){
        if(!isset($data["room"])){
            $this->debug("ROOM:JOIN:ERROR:1: No room specified");
            return;
        }
        if(!isset($this->rooms[$data["room"]])){
            $this->debug("ROOM:JOIN:ERROR:2: Invalid room", $data["room"]);
            return;
        }

        $this->onLeaveRoom($socket);

        if($this->rooms[$data["room"]]->join($socket->player)){
            $socket->player->room = $this->rooms[$data["room"]]->id;
            $this->io->emit("update_room", $this->rooms[$data["room"]]);
            $this->io->emit("update_player", $socket->player);
            $this->debug("ROOM:JOIN:SUCCESS: Player ". $socket->player->username ." joined room #". $this->rooms[$data["room"]]->id);
        }else{
            $this->debug("ROOM:JOIN:ERROR:3: Unnable to join ", $this->rooms[$data["room"]]);
        }
    }

    public function onRoomSpectate(&$socket, $data){
        if(!isset($data["room"])){
            $this->debug("ROOM:SPECT:ERROR:1: No room specified");
            return;
        }
        if(!isset($this->rooms[$data["room"]])){
            $this->debug("ROOM:SPECT:ERROR:2: Invalid room", $data["room"]);
            return;
        }

        $this->onLeaveRoom($socket);

        if($this->rooms[$data["room"]]->spectate($socket->player)){
            $socket->player->room = $this->rooms[$data["room"]]->id;
            $this->io->emit("update_room", $this->rooms[$data["room"]]);
            $this->io->emit("update_player", $socket->player);
        }
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
        return true;
    }

    public function leave(&$player){
        $index = array_search($player, $this->players);
        if($index !== false){
            unset($this->players[$index]);
        }
        
        $index = array_search($player, $this->spectators);
        if($index !== false){
            unset($this->spectators[$index]);
        }
    }

    public function spectate($player){
        if($this->status !== ROOM_STATUS_PLAYING){
            return false;
        }

        $this->spectators[] = $player;
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