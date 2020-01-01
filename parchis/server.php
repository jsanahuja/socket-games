#!/usr/bin/env php
<?php
error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

require_once(dirname(__dir__) . "/vendor/autoload.php");

use Workerman\Worker;
use PHPSocketIO\SocketIO;
use Monolog\Logger;
use Monolog\ErrorHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Games\Core\Controller;
use Games\Games\Parchis\Room;
use Games\Games\Parchis\Player;

$io = new SocketIO(PARCHIS_PORT, array(
    'ssl' => array(
        'local_cert'  => '/etc/letsencrypt/live/games.sowecms.com/cert.pem',
        'local_pk'    => '/etc/letsencrypt/live/games.sowecms.com/privkey.pem',
        'verify_peer' => false,
        'allow_self_signed' => true,
        'verify_peer_name' => false
    )
));

$logger = new Logger("");
// the default date format is "Y-m-d\TH:i:sP"
// the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
$formatter = new LineFormatter("[%datetime%]:%level_name%: %message%\n", "Y-m-d\TH:i:s");
$stream = new StreamHandler(LOG_PATH . PARCHIS_LOG, Logger::DEBUG);
$stream->setFormatter($formatter);
$logger->pushHandler($stream);
// $handler = new ErrorHandler($logger);
// $handler->registerErrorHandler([], false);
// $handler->registerExceptionHandler();
// $handler->registerFatalHandler();

$controller = new Controller($io, $logger, Room::class, Player::class);

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
    $socket->on("room_leave", function() use($socket, $controller){
        $controller->onRoomLeave($socket);
    });
    $socket->on("room_join", function($data) use($socket, $controller){
        $controller->onRoomJoin($socket, $data);
    });
    $socket->on("room_spectate", function($data) use($socket, $controller){
        $controller->onRoomSpectate($socket, $data);
    });

    $socket->on("ready", function() use($socket, $controller){
        $controller->onReady($socket);
    });
    $socket->on("unready", function() use($socket, $controller){
        $controller->onUnready($socket);
    });

    $socket->on("action", function($data) use($socket, $controller){
        $controller->onGameAction($socket, $data);
    });
});

Worker::runAll();
