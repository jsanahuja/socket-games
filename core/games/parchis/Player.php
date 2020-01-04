<?php

namespace Games\Games\Parchis;

use Games\Games\Parchis\Chip;
use Games\Games\Parchis\Color;

class Player extends \Games\Core\Player
{
    private $color;
    private $chips;

    public function __construct(int $id, string $username, $socket)
    {
        parent::__construct($id, $username, $socket);
        
        $this->chips = array();
    }

    public function set_color(Color $color)
    {
        $this->color = $color;
    }

    public function get_color()
    {
        return $this->color;
    }

    public function add_chip(Chip $chip)
    {
        $this->chips[$chip->get_id()] = $chip;
    }

    public function get_chips()
    {
        return $this->chips;
    }

    public function get_chip(int $id)
    {
        return $this->chips[$id];
    }

    public function serialize()
    {
        $chips = array();
        foreach ($this->chips as $chip) {
            $chips[$chip->get_id()] = $chip->serialize();
        }
        return array(
            "id" => $this->id,
            "color" => $this->color->serialize(),
            "chips" => $chips
        );
    }
}
