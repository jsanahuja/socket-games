<?php

namespace Games\Games\Parchis;

use Games\Games\Parchis\Color;
use Games\Games\Parchis\Player;
use Games\Games\Parchis\Chip;
use Games\Games\Parchis\Board;

class Room extends \Games\Core\Room{
    private $turn;
    private $board;
    
    private $dices;
    private $double;
    private $dtimes;

    protected function configure(){
        $this->setNumplayers(4);
    }

    private function assign_player_colors(){
        $colors = [
            Color::$YELLOW,
            Color::$BLUE,
            Color::$RED,
            Color::$GREEN
        ];
        $index = 0;
        foreach($this->players as $player){
            $player->set_color(
                new Color(... $colors[$index])
            );
            $index++;
        }
    }

    private function assign_player_chips(){
        foreach($this->players as $player){
            for($i = 0; $i < 4; $i++){
                $player->add_chip(
                    new Chip($i, $player->get_color())
                );
            }
        }
    }

    private function assign_next_turn(){
        if(!isset($this->turn)){
            $this->turn = array_values($this->players)[rand(0, $this->numplayers-1)];
        }

        $next = false;

        if($this->turn == end($this->players)){
            $next = true;
        }

        foreach($this->players as $player){
            if($next){
                $this->turn = $player;
                break;
            }
            if($player == $this->turn)
                $next = true;
        }
    }

    /**
     * Turn
     */
    public function turn(){
        $this->assign_next_turn();
        $this->dtimes = 0;

        if($this->board->can_pre_move($this->turn)){
            $this->requestThrowDices();
        }else{
            $this->infoCantMove();
            $this->turn();
        }
    }

    public function onThrowDices(){
        $this->$dices = [rand(1,6), rand(1,6)];
        $this->double = $this->dices[0] == $this->dices[1];
        $this->dtimes += $this->double ? 1 : 0;
        
        if($this->dtimes == 3){
            // @TODO: Die? last moved
            $this->infoMaxDoubles();
            $this->turn();
            return;
        }

        $moves = $this->board->get_moves($player, $dices);
        if(sizeof($moves) > 0){
            $this->requestMove($moves);
        }else{
            if($this->double){
                $this->requestThrowDices();
            }else{
                $this->infoCantMove();
                $this->turn();
            }
        }
    }

    public function onChipMove($chip, $to){
        $dices = $this->board->move($this->turn, $chip, $to, $this->dices);
        if($dices === false){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Invalid move to: ". $to);
            return;
        }
        $this->infoMove($chip);
        $this->dices = $dices;

        if(sizeof($this->dices) > 0){
            $moves = $this->board->get_moves($player, $dices);
            if(sizeof($moves) > 0){
                $this->requestMove($moves);
            }else{                
                if($this->double){
                    $this->requestThrowDices();
                }else{
                    $this->infoCantMove();
                    $this->turn();
                }
            }
        }else{
            if($this->double){
                $this->requestThrowDices();
            }else{
                $this->turn();
            }
        }
    }



    protected function start(){
        $this->assign_player_colors();
        $this->assign_player_chips();
        
        $this->board = new Board();
        $this->turn();
    }


    public function infoCantMove(){
        $this->controller->roomEmit($this->id, "skip_move", $this->turn->id);
    }

    public function infoMaxDoubles(){
        $this->controller->roomEmit($this->id, "skip_double", $this->turn->id);
    }

    public function infoMove($chip){
        $this->controller->roomEmit($this->id, "info_move", array(
            "id"   => $this->turn->id,
            "chip" => $chip->get_id(),
            "pos"  => $chip->get_position()
        ));
    }

    public function requestThrowDices(){
        $this->controller->roomEmit($this->id, "dices", $this->turn->id);
    }

    public function requestMove($moves){
        $this->controller->roomEmit($this->id, "move", array(
            "id" => $this->turn->id,
            "dices" => $this->dices,
            "moves" => $moves
        ));
    }

    public function onPlayerAction($player, $data){
        if($player !== $this->turn){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Not his turn ". print_r($data, true));
            return;
        }

        switch($data['action']){
            case "dices":
                $this->onThrowDices();
                break;
            case "move": //(id, to)
                if(!isset($data["id"]) || !isset($data["to"]) || ($chip = $socket->player->get_chip($data["id"])) === false){
                    $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Invalid move ". print_r($data, true));
                    return;
                }
                $this->onChipMove($chip, $data["to"]);
                break;
            default:
                $this->logger->error(__FUNCTION__.":".__LINE__ .":". $socket->player .": Undefined action ". print_r($data, true));
                break;
        }
    }

    protected function finish(){

    }
}