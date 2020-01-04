<?php

namespace Games\Core;

abstract class Player
{
    public $id;
    public $username;
    public $room;
    private $socket;

    public function __construct(int $id, string $username, $socket)
    {
        $this->id = $id;
        $this->username = $username;
        $this->room = null;
        $this->socket = $socket;
    }

    public function getSocket()
    {
        return $this->socket;
    }

    public function __toString()
    {
        return $this->id . ":" . $this->username;
    }
}
