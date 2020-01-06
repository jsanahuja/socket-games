<?php

namespace Games\Games\Parchis;

use Games\Utils\GameSerializable;
use Games\Utils\Comparable;

class Color implements GameSerializable, Comparable
{
    public static $COLORS = [
        [0, "yellow",  5, 68, 69,  76],
        [1, "blue",   22, 17, 77,  84],
        [2, "red",    39, 34, 85,  92],
        [3, "green",  56, 51, 93, 100]
    ];

    protected $id;
    protected $name;
    protected $initial;
    protected $breaker;
    protected $postbreak;
    protected $finish;

    public function __construct($id, $name, $initial, $breaker, $postbreak, $finish)
    {
        $this->id        = $id;
        $this->name      = $name;
        $this->initial   = $initial;
        $this->breaker   = $breaker;
        $this->postbreak = $postbreak;
        $this->finish    = $finish;
    }

    public function get_id()
    {
        return $this->id;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function get_next($position)
    {
        switch ($position) {
            case -1:
                return $this->initial;
            case $this->breaker:
                return $this->postbreak;
            case false: // after finish
            case $this->finish:
                return false;
            case 68:
                return 1;
            default:
                return $position+1;
        }
    }

    public function jump($position, $jump)
    {
        if ($position == -1) {
            if ($jump == 5) {
                return $this->initial;
            } else {
                return false;
            }
        }

        while ($jump != 0) {
            $position = $this->get_next($position);
            $jump--;
        }
        return $position;
    }

    public function get_initial()
    {
        return $this->initial;
    }

    public function get_breaker()
    {
        return $this->breaker;
    }
    
    public function get_postbreak()
    {
        return $this->postbreak;
    }

    public function get_finish()
    {
        return $this->finish;
    }

    public function equals(Comparable $object){
        return get_class($this) === get_class($object) && $this->id === $object->getId();
    }
    
    public function gameSerialize()
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "initial" => $this->initial,
            "breaker" => $this->breaker,
            "postbreak" => $this->postbreak,
            "finish" => $this->finish,
        ];
    }
}
