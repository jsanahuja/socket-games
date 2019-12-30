<?php

namespace Games\Games;

use Games\Core\Room;

class Parchis extends Room{

    private function throw_dice(){
        return rand(1,6);
    }

    protected function configure(){
        $this->setNumplayers(4);
    }

    protected function start(){
        // raffle start
        $raffle = array();
        foreach($this->players as $player){
            $raffle[] = array(
                "id" => $player->id,
                "num" => $this->throw_dice()
            );
        }
        
        $this->controller->roomEmit($this->id, "play", $raffle);
    }

    protected function finish(){

    }
}