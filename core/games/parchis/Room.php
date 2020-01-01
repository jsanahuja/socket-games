<?php

namespace Games\Games\Parchis;

use Games\Core\ACK;

use Games\Games\Parchis\Color;
use Games\Games\Parchis\Player;
use Games\Games\Parchis\Chip;
use Games\Games\Parchis\Board;

class Room extends \Games\Core\Room{
    private $turn;
    private $board;

    private $acks;
    
    private $original_dices;

    private $dices;
    private $doubles;

    protected function configure(){
        $this->acks = array();

        $this->turn = false;
        $this->setNumplayers(4);
        $this->original_dices = array();
        $this->dices = array();

        $this->throw_dices = false;
        $this->make_move = false;
    }

    /**
     * Init functions
     */
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

    /**
     * Turn management
     */
    private function assign_next_turn(){
        if($this->turn === false){
            $this->turn = array_values($this->players)[rand(0, $this->numplayers-1)];
            return;
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

    public function turn(){
        $this->assign_next_turn();
        $this->doubles = 0;

        if($this->board->can_pre_move($this->turn)){
            $this->requestThrowDices();
        }else{
            $this->logger->debug("cant premove", $this->turn->serialize());
            $this->infoCantMove();
            $this->turn();
        }
    }

    protected function process(){
        if($this->doubles == 3){
            // @TODO: Die? last moved
            $this->infoMaxDoubles();
            $this->turn();
            return;
        }

        
        // Moves
        $moves = $this->board->get_moves($this->turn, $this->dices);
        print_r(array(
            "dices"=> $this->dices,
            "moves"=> $moves
        ));

        if(sizeof($this->dices) == 0){
            // No more dices
            if($this->double){
                // Double => again
                $this->requestThrowDices();
            }else{
                // Not double, next turn
                $this->turn();
            }
        }else{
            // Remaining dices
            if(sizeof($moves) == 0){
                // Can't move => next turn
                $this->infoCantMove();
                $this->turn();
            }else{
                // Can move
                $this->requestMove($moves);
            }
        }
    }

    /**
     * Dices
     */

    protected function requestThrowDices(){
        $this->throw_dices = true;
        $this->controller->roomEmit($this->id, "dices", $this->turn->id);
    }

    protected function onThrowDices(){
        if(!$this->throw_dices)
            return false;
        $this->throw_dices = false;

        $this->dices = [rand(1,6), rand(1,6)];
        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $this->turn .": throw dices ". $this->dices[0] ."," . $this->dices[1]. " (Doubles: ". $this->doubles .")");


        // @TODO: Needed?
        $this->original_dices = $this->dices;

        // Doubles
        if($this->dices[0] == $this->dices[1])
            $this->doubles += 1;
        else
            $this->doubles = 0;

        $this->process();
    }

    /**
     * Moves
     */
    protected function requestMove($moves){
        $this->make_move = true;
        $this->controller->roomEmit($this->id, "move", array(
            "id" => $this->turn->id,
            "dices" => $this->dices,
            "moves" => $moves
        ));
    }

    protected function onChipMove($chip, $to){
        if(!$this->make_move)
            return;
        $this->make_move = false;
    
        if(($dices = $this->board->move($this->turn, $chip, $to, $this->dices)) === false){
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $this->turn .": Invalid move ". $chip ." to: ". $to);
            return;
        }

        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $this->turn .": move ". $chip ." to ". $to);
        $this->infoMove($chip);
        $this->dices = $dices;

        $this->process();
    }
    
    protected function infoMove($chip){
        $this->controller->roomEmit($this->id, "info_move", array(
            "id"   => $this->turn->id,
            "chip" => $chip->get_id(),
            "to"  => $chip->get_position()
        ));
    }


    /**
     * Game management
     */
    protected function onACK($player, $event){
        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $player .": ack ". $event);
        $this->acks[$event]->ack($player);
    }

    public function onPlayerAction($player, $data){
        switch($data['action']){
            case "dices":
                if($player !== $this->turn){
                    $this->logger->error(__FUNCTION__.":".__LINE__ .":". $player .": Not his turn ". print_r($data, true));
                    return;
                }
                $this->onThrowDices();
                break;
            case "move":
                if($player !== $this->turn){
                    $this->logger->error(__FUNCTION__.":".__LINE__ .":". $player .": Not his turn ". print_r($data, true));
                    return;
                }
                if(!isset($data["id"]) || !isset($data["to"]) || ($chip = $player->get_chip($data["id"])) === false){
                    $this->logger->error(__FUNCTION__.":".__LINE__ .":". $player .": Invalid move ". print_r($data, true));
                    return;
                }
                $this->onChipMove($chip, $data["to"]);
                break;
            case "ack":
                if(!isset($data["event"]) || !isset($this->acks[$data["event"]])){
                    $this->logger->error(__FUNCTION__.":".__LINE__ .":". $player .": No ACK event ". print_r($data, true));
                    return;
                }
                $this->onACK($player, $data["event"]);
                break;
            default:
                $this->logger->error(__FUNCTION__.":".__LINE__ .":". $player .": Undefined action ". print_r($data, true));
                break;
        }
    }

    protected function infoPlay(){
        $this->controller->roomEmit($this->id, "play", $this->serialize());
    }

    protected function infoCantMove(){
        $this->controller->roomEmit($this->id, "skip_move", $this->turn->id);
    }

    protected function infoMaxDoubles(){
        $this->controller->roomEmit($this->id, "skip_double", $this->turn->id);
    }

    protected function start(){
        $this->assign_player_colors();
        $this->assign_player_chips();
        
        $this->board = new Board();
        
        // @TODO: First chip out?
        // $dices = $this->board->move($this->turn, $chip, $to, $this->dices);
        
        // @TODO: What if never ACK?
        $this->acks["play"] = new ACK($this->players, function(){
            $this->turn();
        });
        $this->infoPlay();
    }

    protected function finish(){

    }

    /**
     * Serialization
     */
    public function serialize(){
        $players = array();
        foreach($this->players as $player){
            $players[$player->id] = $player->serialize();
        }
        return array(
            "players" => $players,
            "turn" => $this->turn->id,
            "dices" => $this->dices,
            "original_dices" => $this->original_dices
        );
    }
}