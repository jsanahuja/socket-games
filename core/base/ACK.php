<?php

namespace Games\Core;

class Ack
{
    private $players;
    private $callback;

    public function __construct($players, $callback)
    {
        $this->players = $players;
        $this->callback = $callback;
    }

    public function ack($player)
    {
        $idx = array_search($player, $this->players);
        if ($idx !== false) {
            unset($this->players[$idx]);
            if (sizeof($this->players) == 0) {
                ($this->callback)();
            }
        }
    }
}
