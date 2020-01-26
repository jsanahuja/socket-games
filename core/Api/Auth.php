<?php

namespace Api;

use \Models\User;
use \Sowe\Framework\Database;
use \Sowe\Framework\HTTP\Request\JSONEndpoint;

class Auth extends JSONEndpoint{
    protected $database;
    protected $argv;

    public function __construct(Database $database, ...$argv)
    {
        $this->database = $database;
        $this->argv = $argv;
        parent::__construct();
    }

    public function post(){
        if (isset($this->variables["username"])) {
            $this->validate_variable("username", true, "string");
            $this->validate_variable("password", true, "string");

            $users = new User($this->database);
            try{
                $this->response["auth"] = $users->authUsername(
                    $this->variables["username"],
                    $this->variables["password"]
                );
            }catch(\Exception $e){
                $this->throw_error("The username entered does not exists", 404);
            }
        }else if(isset($this->variables["email"])){
            $this->validate_variable("email", true, "email");
            $this->validate_variable("password", true, "string");

            $users = new User($this->database);
            try{
                $this->response["auth"] = $token = $users->authEmail(
                    $this->variables["email"],
                    $this->variables["password"]
                );
            }catch(\Exception $e){
                $this->throw_error("The email address entered is not registered", 404);
            }
        }else{
            $this->validate_variable("id", true, "int");
            $this->validate_variable("token", true, "string");

            $users = new User($this->database);
            try{
                $this->response["auth"] = $token = $users->authToken(
                    $this->variables["id"],
                    $this->variables["token"]
                );
            }catch(\Exception $e){
                $this->throw_error("The user id entered is not registered", 404);
            }
        }
    }
}
