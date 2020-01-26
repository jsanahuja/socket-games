<?php

namespace Api;

use \Models\User;
use \Sowe\Framework\Database;
use \Sowe\Framework\HTTP\Request\JSONEndpoint;

class Users extends JSONEndpoint{
    protected $database;
    protected $argv;

    public function __construct(Database $database, ...$argv)
    {
        $this->database = $database;
        $this->argv = $argv;
        parent::__construct();
    }

    public function get(){
        $users = new User($this->database);

        if(isset($this->argv[0])){
            $this->variables['id'] = $this->argv[0];
            $this->validate_variable("id", true, "int");

            $this->response["user"] = $users->list(
                ["id", "username", "register", "credits", "server"],
                ["id", "=", $this->variables['id']]
            );
        }else{
            $this->response["users"] = $users->list(
                ["id", "username"]
            );
        }
    }

    public function post(){
        $this->validate_variable("username",    true, "string");
        $this->validate_variable("email",       true, "email");
        $this->validate_variable("password",    true, "string");

        $users = new User($this->database);
        try{
            $users->new()
                ->setData("username", $this->variables["username"])
                ->setData("email", $this->variables["email"])
                ->setEncryptedData("password", $this->variables["password"])
                ->save();
        }catch(\Exception $e){
            if ($this->database->errno === 1062 /* MYSQLI_ERRNO_DUPLICATE_KEY */ ) {
                $this->throw_error("Username or Email already registered", 409);
            }else{
                $this->throw_error("Unknown error");
            }
        }
    }
}
