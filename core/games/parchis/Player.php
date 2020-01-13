<?php

namespace Games\Games\Parchis;

use Games\Games\Parchis\Chip;
use Games\Games\Parchis\Color;
use Games\Utils\Mapping;

class Player extends \Games\Core\Player
{
    private $color;
    private $chips;

    public function __construct($id, string $username, $socket)
    {
        parent::__construct($id, $username, $socket);
        
        $this->chips = new Mapping();
    }

    /**
     * Color
     */
    public function set_color(Color $color)
    {
        $this->color = $color;
    }

    public function get_color()
    {
        return $this->color;
    }

    /**
     * Chips
     */
    public function add_chip(Chip $chip)
    {
        $this->chips->add($chip);
    }

    public function get_chips()
    {
        return $this->chips;
    }

    public function get_chip($id)
    {
        return $this->chips->get($id);
    }

    /**
     * Serialization
     */
    public function gameSerialize()
    {
        return [
            "id" => $this->id,
            "color" => $this->color->gameSerialize(),
            "chips" => $this->chips->gameSerialize()
        ];
    }
}
