<?php

use Workerman\Lib\Timer;

class Controller{
    private $io;
    private $logger;

    private $rooms;
    private $players;

    private $next_id;

    public function __construct($io, $logger){
        $this->io = $io;
        $this->logger = $logger;

        $this->players = array();
        $this->rooms = array();

        $this->next_id = 0;

        for($i = 1; $i <= PARCHIS_ROOMS; $i++){
            $this->rooms[$i] = new Room($i, $this, $logger);
        }
    }

    public function onConnect($socket, $data){
        if(!isset($data['username'])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .": No username");
            return;
        }

        if(isset($socket->player)){
            $this->onDisconnect($socket);
        }

        // @TODO: Auth / unique nickname
        $socket->player = new Player(++$this->next_id, $data['username'], $socket);
        $this->players[$socket->player->id] = $socket->player;
        
        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player);
        $this->io->emit('user_login', $socket->player);
        $socket->emit("data", array(
            "you" => $socket->player->id,
            "players" => $this->players,
            "rooms" => $this->rooms
        ));
    }

    public function onDisconnect($socket){
        if(!isset($socket->player) || !isset($this->players[$socket->player->id])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .": Socket had no player");
            return;
        }

        if($socket->player->room != null){
            $this->onRoomLeave($socket);
        }

        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player);
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
                $this->io->emit("update_room", $this->rooms[$socket->player->room]);
                
                $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .": left the room " . $this->rooms[$socket->player->room]);
            }
            $socket->player->room = null;
            $this->io->emit("update_player", $socket->player);
        }
    }

    public function onRoomJoin($socket, $data){
        if(!isset($data["room"])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": No room specified", $data);
            return;
        }
        if(!isset($this->rooms[$data["room"]])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Invalid room ", $data);
            return;
        }

        $this->onRoomLeave($socket);

        if($this->rooms[$data["room"]]->join($socket->player)){
            $socket->player->room = $this->rooms[$data["room"]]->id;
            $this->io->emit("update_room", $this->rooms[$data["room"]]);
            $this->io->emit("update_player", $socket->player);
            $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .": joined the room " . $this->rooms[$data["room"]]);
        }else{
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": can't join ", $this->rooms[$data["room"]]);
        }
    }

    public function onRoomSpectate($socket, $data){
        if(!isset($data["room"])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": No room specified", $data);
            return;
        }
        if(!isset($this->rooms[$data["room"]])){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Invalid room ", $data);
            return;
        }

        $this->onRoomLeave($socket);

        if($this->rooms[$data["room"]]->spectate($socket->player)){
            $socket->player->room = $this->rooms[$data["room"]]->id;
            $this->io->emit("update_room", $this->rooms[$data["room"]]);
            $this->io->emit("update_player", $socket->player);
            $this->logger->info(__FUNCTION__.":".__LINE__ .":". $socket->player .": is now spectating the room " . $this->rooms[$data["room"]]);
        }else{
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": can't spectate ", $this->rooms[$data["room"]]);
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
        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $this->rooms[$id] .":".$event);
        if($data !== null)
            $this->io->to("room" . $id)->emit($event, $data);
        else
            $this->io->to("room" . $id)->emit($event);
    }
}




class Room{
    public $id;
    public $status;
    public $mode;
    public $max_players;

    private $ready;
    public $players;
    public $spectators;

    private $controller;
    private $logger;

    public function __construct($id, $controller, $logger){
        $this->id = $id;
        $this->status = ROOM_STATUS_EMPTY;
        $this->mode = ROOM_MODE_INDIVIDUAL;
        $this->max_players = 4;
        $this->players = array();
        $this->spectators = array();

        $this->controller = $controller;
        $this->logger = $logger;
    }

    private function setStatus($status){
        if($this->status !== $status){
            $this->status = $status;

            switch($this->status){
                case ROOM_STATUS_READY:
                    $this->prepare();
                    break;
                case ROOM_STATUS_PLAYING:
                    $this->setup();
                default:
                    break;
            }
        }
    }

    public function join($player){
        if($this->status != ROOM_STATUS_EMPTY && $this->status != ROOM_STATUS_WAITING)
            return false;

        if($this->max_players <= sizeof($this->players))
            return false;

        $this->players[] = $player;
        $this->controller->roomJoin($this->id, $player);

        if(sizeof($this->players) == $this->max_players)
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

        if($this->status !== ROOM_STATUS_READY){
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

                if(sizeof($this->ready) == $this->max_players)
                    $this->setStatus(ROOM_STATUS_PLAYING);
            }
        }
    }

    public function playerUnready($player){
        if($this->status === ROOM_STATUS_READY){
            $this->controller->onRoomLeave($player->getSocket());
            $this->controller->roomEmit($this->id, "unready");
        }
    }

    public function prepare(){
        $this->ready = array();
        $this->controller->roomEmit($this->id, "ready", array("time" => GAME_READY_TIME));
                
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

    private function throw_dice(){
        return rand(1,6);
    }

    public function setup(){
        // raffle start
        $raffle = array();
        foreach($this->players as $player){
            $raffle[] = array(
                "id" => $player->id,
                "num" => $this->throw_dice()
            );
        }
        
        $this->controller->roomEmit($this->id, "play", $raffle);
    }

    public function __toString(){
        return "#" . $this->id;
    }
}

class Player{
    public $id;
    public $username;
    public $room;
    private $socket;

    public function __construct($id, $username, $socket){
        $this->id = $id;
        $this->username = $username;
        $this->room = null;
        $this->socket = $socket;
    }

    public function getSocket(){
        return $this->socket;
    }

    public function __toString(){
        return $this->id . ":" . $this->username;
    }
}