<?php

namespace Models;

use Sowe\Framework\AbstractObject;
use Sowe\Framework\Keychain;

class User extends AbstractObject{
    protected static $table = "users";
    protected static $key = "id";

    /**
     * Authentication
     */
    public function authUsername($username, $password){
        $user = $this->database
            ->select(static::$table)
            ->fields("id", "username", "email", "password")
            ->condition("username", "=", $username)
            ->run()
            ->fetchOne();
        if (!$user) {
            throw new \Exception("Invalid user");
        }
        if(Keychain::hash_verify($user['password'], $password)){
            return [
                "id" => $user['id'],
                "username" => $user['username'],
                "email" => $user['email'],
                "token" => $this->generateToken($user['id'], $user['username'])
            ];
        }
        return false;
    }

    public function authEmail($email, $password){
        $user = $this->database
            ->select(static::$table)
            ->fields("id", "username", "email", "password")
            ->condition("email", "=", $email)
            ->run()
            ->fetchOne();
        if (!$user) {
            throw new \Exception("Invalid user");
        }
        if(Keychain::hash_verify($user['password'], $password)){
            return [
                "id" => $user['id'],
                "username" => $user['username'],
                "email" => $user['email'],
                "token" => $this->generateToken($user['id'], $user['username'])
            ];
        }
        return false;
    }

    public function authToken($id, $token){
        $user = $this->get($id, ["id", "username", "email"]);
        if (!$user) {
            throw new \Exception("Invalid user");
        }
        if($this->verifyToken($user['id'], $user['username'], $token)){
            return [
                "id" => $user['id'],
                "username" => $user['username'],
                "email" => $user['email'],
                "token" => $this->generateToken($user['id'], $user['username'])
            ];
        }
        return false;
    }

    /**
     * Token Management
     */
    public function generateToken($id, $username){
        return Keychain::encrypt(
            $id, $username, time() + LAPSE_TOKEN_VALIDITY
        );
    }

    public function verifyToken($id, $username, $token){
        $data = Keychain::decrypt($token);
        return $data[0] === $id && $data[1] === $username && $data[2] >= time();
    }

    /**
     * AbstractObject password treatment
     */
    public function setEncryptedData($field, $value){
        if($field === static::$key){
            throw new \Exception("Cannot set object identifier");
        }
        $this->data[$field] = Keychain::hash($value);
        return $this;
    }
}
