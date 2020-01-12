<?php

namespace Games\Core;

use Games\Core\Room;
use Games\Utils\Mapable;
use Games\Utils\Comparable;

abstract class Player implements Mapable
{
    protected $id;
    protected $username;
    protected $room;
    protected $socket;

    public function __construct(int $id, string $username, $socket)
    {
        $this->id = $id;
        $this->username = $username;
        $this->room = false;
        $this->socket = $socket;
    }

    public function equals(Comparable $object){
        return get_class($this) === get_class($object) && $this->id === $object->getId();
    }

    /**
     * Mapable
     */
    public function getId(){
        return $this->id;
    }

    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * Room management
     */
    public function setRoom(Room $room){
        $this->room = $room;
    }

    public function unsetRoom(){
        $this->room = false;
    }

    public function getRoom(){
        return $this->room;
    }

    /**
     * Serialization
     */
    public function jsonSerialize(){
        return [
            "id" => $this->id,
            "username" => $this->username,
            "room" => $this->room === false ? false : $this->room->getId()
        ];
    }

    abstract public function gameSerialize();

    /**
     * String conversion
     */
    public function __toString()
    {
        return $this->id . ":" . $this->username;
    }

}
