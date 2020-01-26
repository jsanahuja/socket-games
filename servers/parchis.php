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

use Sowe\Framework\Database;

use Games\Core\Controller;
use Games\Games\Parchis\Room;
use Games\Games\Parchis\Player;

$database = new Database(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

$servers = $database->select("servers")
    ->fields("id", "port")
    ->condition("game", "=", "parchis")
    ->run()->fetchAll();

foreach($servers as $server){

    $logger = new Logger("");
    $formatter = new LineFormatter("[%datetime%]:%level_name%: %message%\n", "Y-m-d\TH:i:s");
    $stream = new StreamHandler(LOG_PATH . sprintf(PARCHIS_LOG, $server['id']), Logger::DEBUG);
    $stream->setFormatter($formatter);
    $logger->pushHandler($stream);
    $handler = new ErrorHandler($logger);
    $handler->registerErrorHandler([], false);
    $handler->registerExceptionHandler();
    $handler->registerFatalHandler();


    $io = new SocketIO($server['port'], array(
        'ssl' => array(
            'local_cert'  => CERT_CA,
            'local_pk'    => CERT_KEY,
            'verify_peer' => false,
            'allow_self_signed' => true,
            'verify_peer_name' => false
        )
    ));

    $controller = new Controller($server['id'], $database, $io, $logger, Room::class, Player::class);

    $io->on('connection', function ($socket) use ($io, $controller) {

        $socket->on("auth", function ($data) use ($socket, $controller) {
            $controller->onConnect($socket, $data);
        });
        $socket->on("disconnect", function () use ($socket, $controller) {
            $controller->onDisconnect($socket);
        });
        $socket->on("message", function ($data) use ($socket, $controller) {
            $controller->onMessage($socket, $data);
        });
        
        $socket->on("leave", function () use ($socket, $controller) {
            $controller->onRoomLeave($socket);
        });
        $socket->on("join", function ($data) use ($socket, $controller) {
            $controller->onRoomJoin($socket, $data);
        });
        $socket->on("spectate", function ($data) use ($socket, $controller) {
            $controller->onRoomSpectate($socket, $data);
        });
        $socket->on("kick", function ($data) use ($socket, $controller) {
            $controller->onKick($socket, $data);
        });
    
        $socket->on("ready", function () use ($socket, $controller) {
            $controller->onReady($socket);
        });
        $socket->on("unready", function () use ($socket, $controller) {
            $controller->onUnready($socket);
        });
    
        $socket->on("action", function ($data) use ($socket, $controller) {
            $controller->onGameAction($socket, $data);
        });
    });

}

Worker::runAll();
