<?php

namespace Games\Games\Parchis;

class Color{
    public static $YELLOW = [5, 68, 69,  76];
    public static $BLUE =   [22, 17, 77,  84];
    public static $RED =    [39, 34, 85,  92];
    public static $GREEN =  [56, 51, 93, 100];

    public function __construct($initial, $breaker, $postbreak, $finish){
        $this->initial   = $initial;
        $this->breaker   = $breaker;
        $this->postbreak = $postbreak;
        $this->finish    = $finish;
    }

    public function get_next($position){
        switch($position){
            case -1:
                return $this->initial;
            case $this->breaker:
                return $this->postbreak;
            case $this->finish:
                return false;
            case 68:
                return 1;
            default:
                return $position+1;
        }
    }

    public function jump($position, $jump){
        while($jump != 0){
            $position = $this->get_next($position);
            $jump--;
        }
        return $position;
    }

    public function get_initial(){
        return $this->initial;
    }
}