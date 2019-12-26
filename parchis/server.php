#!/usr/bin/env php
<?php
error_reporting(E_ALL);

/* Permitir al script esperar para conexiones. */
set_time_limit(0);

/* Activar el volcado de salida implícito, así veremos lo que estamo obteniendo
* mientras llega. */
ob_implicit_flush();

require_once("../vendor/autoload.php");
require_once("../config.php");
require_once("defines.php");
require_once("Parchis.php");

use Workerman\Worker;
use PHPSocketIO\SocketIO;

$io = new SocketIO(PARCHIS_PORT, array(
    'ssl' => array(
        'local_cert'  => '/etc/letsencrypt/live/games.sowecms.com/cert.pem',
        'local_pk'    => '/etc/letsencrypt/live/games.sowecms.com/privkey.pem',
        'verify_peer' => false,
        'allow_self_signed' => true,
        'verify_peer_name' => false
    )
));

$controller = new Controller($io);

$io->on('connection', function($socket) use($io) {
    global $controller;

    $socket->on("login", function($data) use($socket, $controller){
        $controller->onConnect($socket, $data);
    });
    $socket->on("disconnect", function() use($socket, $controller){
        $controller->onDisconnect($socket);
    });
    $socket->on("message", function($data) use($socket, $controller){
        $controller->onMessage($socket, $data);
    });
    $socket->on("room_leave", function($data) use($socket, $controller){
        $controller->onRoomLeave($socket, $data);
    });
    $socket->on("room_join", function($data) use($socket, $controller){
        $controller->onRoomLeave($socket, $data);
    });
    $socket->on("room_spectate", function($data) use($socket, $controller){
        $controller->onRoomSpectate($socket, $data);
    });


    $socket->on("room_join", function($data) use($socket, $io){
        global $controller;

        if(!isset($data["room"])){
            return;
        }

        if($socket->player->room !== null){
            $room = $socket->player->room;
            if($controller->leave_room($socket->player)){
                $io->emit("update_room", $controller->get_room($room));
            }
        }

        if($controller->join_room($socket->player, $data["room"])){
            $io->emit("update_player", $socket->player);
            $io->emit("update_room", $controller->get_room($data["room"]));
        }
    });
    $socket->on("room_join_spectator", function($data) use($socket, $io){
        global $controller;

        if(!isset($data["room"])){
            return;
        }

        if($socket->player->room !== null){
            $room = $socket->player->room;
            if($controller->leave_room($socket->player)){
                $io->emit("update_room", $controller->get_room($room));
            }
        }

        if($controller->join_room_spectator($socket->player, $data["room"])){
            $io->emit("update_player", $socket->player);
            $io->emit("update_room", $controller->get_room($data["room"]));
        }
    });
});

Worker::runAll();
