<?php

namespace Games\Games\Parchis;

use Games\Games\Parchis\Color;
use Games\Utils\Mapable;
use Games\Utils\Comparable;

class Chip implements Mapable
{
    private $id;
    private $color;
    private $position;

    public function __construct($id, Color $color, $position = -1)
    {
        $this->id = $id;
        $this->color = $color;
        $this->position = $position;
    }

    public function getId()
    {
        return $this->id;
    }

    public function get_uuid()
    {
        return $this->color->get_name() . $this->getId();
    }

    public function get_color()
    {
        return $this->color;
    }

    public function set_position($position)
    {
        $this->position = $position;
    }
    
    public function get_position()
    {
        return $this->position;
    }
    
    public function equals(Comparable $object){
        return get_class($this) === get_class($object) && 
               $this->id === $object->getId() && 
               $this->color->equals($chip->get_color());
    }
    
    public function jsonSerialize(){
        return $this->gameSerialize();
    }

    public function gameSerialize()
    {
        return [
            "id" => $this->id,
            "position" => $this->position
        ];
    }
    
    public function __toString()
    {
        return "c" . $this->id . "-" . $this->color->get_name();
    }
}
