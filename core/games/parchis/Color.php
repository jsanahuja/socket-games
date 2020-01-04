<?php

namespace Games\Games\Parchis;

class Color{
    public static $YELLOW = [0, "yellow",  5, 68, 69,  76];
    public static $BLUE =   [1, "blue",   22, 17, 77,  84];
    public static $RED =    [2, "red",    39, 34, 85,  92];
    public static $GREEN =  [3, "green",  56, 51, 93, 100];

    private $id;
    private $name;
    private $initial;
    private $breaker;
    private $postbreak;
    private $finish;

    public function __construct($id, $name, $initial, $breaker, $postbreak, $finish){
        $this->id        = $id;
        $this->name      = $name;
        $this->initial   = $initial;
        $this->breaker   = $breaker;
        $this->postbreak = $postbreak;
        $this->finish    = $finish;
    }

    public function get_id(){
        return $this->id;
    }

    public function get_name(){
        return $this->name;
    }

    public function get_next($position){
        switch($position){
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

    public function jump($position, $jump){
        if($position == -1){
            if($jump == 5)
                return $this->initial;
            else
                return false;
        }

        while($jump != 0){
            $position = $this->get_next($position);
            $jump--;
        }
        return $position;
    }

    public function get_initial(){
        return $this->initial;
    }

    public function equals($color){
        return $this->id == $color->get_id();
    }

    public function serialize(){
        return array(
            "id" => $this->id,
            "name" => $this->name,
            "initial" => $this->initial,
            "breaker" => $this->breaker,
            "postbreak" => $this->postbreak,
            "finish" => $this->finish,
        );
    }
}