<?php

namespace Games\Games\Parchis;

use Games\Games\Parchis\Color;

class Chip{
    private $id;
    private $color;
    private $position;

    public function __construct($id, Color $color, $position = -1){
        $this->id = $id;
        $this->color = $color;
        $this->position = $position;
    }

    public function get_id(){
        return $this->id;
    }

    public function get_color(){
        return $this->color;
    }

    public function set_position($position){
        $this->position = $position;
    }
    
    public function get_position(){
        return $this->position;
    }

    public function equals($chip){
        return $this->id == $chip->get_id && $this->color->equals($chip->get_color());
    }

    public function serialize(){
        return array(
            "id" => $this->id,
            "position" => $this->position
        );
    }
    
    public function __toString(){
        return "c" . $this->id . "-" . $this->color->get_name();
    }
}