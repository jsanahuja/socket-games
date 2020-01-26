<?php

namespace Api;

use \Models\Server;
use \Sowe\Framework\Database;
use \Sowe\Framework\HTTP\Request\JSONEndpoint;

class Servers extends JSONEndpoint{
    protected $database;
    protected $argv;

    public function __construct(Database $database, ...$argv)
    {
        $this->database = $database;
        $this->argv = $argv;
        parent::__construct();
    }

    public function get(){
        if(isset($this->argv[0])){
            $this->variables->game = $this->argv[0];
        }
        $this->validate_variable("game", false, "string");

        $servers = new Server($this->database);
        if(isset($this->variables->game)){
            $this->response["servers"] = $servers->list("*", ["game", "=", $this->variables->game]);
        }else{
            $this->response["servers"] = $servers->list("*");
        }
    }
}
