<?php

namespace Games\Core;

use Games\Utils\Mapping;

class Ack
{
    private $players;
    private $callback;

    public function __construct(Mapping $players, \Callable $callback)
    {
        $this->players = clone $players;
        $this->callback = $callback;
    }

    public function ack($player)
    {
        if(!$this->players->contains($player)){
            $this->players->remove($player);

            if(sizeof($this->players == 0)){
                ($this->callback)();
            }
        }
    }
}
