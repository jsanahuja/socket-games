<?php

namespace Playform;

use \Models\Server;
use \Sowe\Framework\Database;
use \Sowe\Framework\HTTP\Request\HTMLEndpoint;

class GameManager extends HTMLEndpoint{
    protected $database;
    protected $game;
    protected $server;

    public function __construct(Database $database, $game, $server){
        $this->database = $database;
        $this->game = $game;
        $this->server = $server;
        parent::__construct();
    }

    // public function post(){

    // }

    public function get(){
        $this->file = dirname(dirname(__dir__)) . "/clients/" . $this->game . ".php"; 

        if(!file_exists($this->file)){
            throw new \Exception("Game client file not found");
        }

        $servers = new Server($this->database);
        $server = $servers->list(["name", "game", "port"], ["path", "=", $this->server]);

        if(sizeof($server) === 0){
            throw new \Exception("Game server not found");
        }
        $server = reset($server);

        $this->replaces["title"] = ucfirst($server['game']) . " - " . $server['name']  . " - " . SITE_NAME;
        $this->replaces["port"] = $server['port'];
        $this->replaces["id"] = "Math.floor(Math.random() * 99999)";
        $this->replaces["username"] = "User";
        $this->replaces["token"] = 1;
    }

}