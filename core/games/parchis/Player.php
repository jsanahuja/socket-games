<?php

namespace Games\Games\Parchis;

use Games\Games\Parchis\Chip;
use Games\Games\Parchis\Color;

class Player extends \Games\Core\Player{
    private $color;
    private $chips;

    public function set_color(Color $color){
        $this->color = $color;
    }

    public function get_color(){
        return $this->color;
    }

    public function add_chip(Chip $chip){
        if(gettype($this->chips) != "array")
            $this->chips = [];
        $this->chips[$chip->get_id()] = $chip;
    }

    public function get_chips(){
        return $this->chips;
    }

    public function get_chip($id){
        if(isset($this->chips[$id]))
            return $this->chips[$id];
        return false;
    }
}
