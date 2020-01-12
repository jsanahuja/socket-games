<?php

namespace Games\Games\Parchis;

use Games\Core\ACK;

use Games\Games\Parchis\Color;
use Games\Games\Parchis\Player;
use Games\Games\Parchis\Chip;
use Games\Games\Parchis\Board;

class Room extends \Games\Core\Room
{
    private $turn;
    private $board;

    private $acks;
    
    private $dices;
    private $doubles;

    protected function configure()
    {
        $this->acks = array();

        $this->turn = false;
        $this->numplayers = 2;
        $this->dices = array();

        $this->throw_dices = false;
        $this->make_move = false;
    }

    /**
     * Init functions
     */
    private function assign_player_colors()
    {
        $index = 0;
        foreach($this->players as $player){
            $player->set_color(new Color(... Color::$COLORS[$index++]));
            
            // Make 1v1 in opposite sides
            if ($this->numplayers == 2) {
                $index++;
            }
        }
    }

    private function assign_player_chips()
    {
        foreach ($this->players as $player) {
            for ($i = 0; $i < 4; $i++) {
                $player->add_chip(
                    new Chip($i, $player->get_color())
                );
            }
        }
    }

    /**
     * Turn management
     */
    private function assign_next_turn()
    {
        // First turn
        if ($this->turn === false) {
            $this->turn = $this->players->values()[rand(0, sizeof($this->players))];
            print_r(gettype($this->turn));
            print_r($this->turn->getId());
            return;
        }

        $this->turn = $this->players->next($this->turn);
        print_r(gettype($this->turn));
        print_r($this->turn->getId());
    }

    public function turn()
    {
        $this->assign_next_turn();
        $this->doubles = 0;

        if ($this->board->can_premove($this->turn)) {
            $this->requestThrowDices();
        } else {
            $this->logger->debug("cant premove", $this->turn->serialize());
            $this->infoCantMove();
            $this->turn();
        }
    }

    protected function process()
    {
        if ($this->doubles == 3) {
            $killed = $this->board->kill_last_moved();
            if ($killed !== false) {
                $this->infoDie($chip);
            }
            $this->infoMaxDoubles();
            $this->turn();
            return;
        }
        
        // Moves
        $moves = $this->board->get_moves($this->turn, $this->dices);
        fwrite(STDERR, print_r(array(
            "dices"=> $this->dices,
            "moves"=> $moves
        ), true).  PHP_EOL);

        if (sizeof($moves) > 0) {
            // Can move
            $this->requestMove($moves);
        } else {
            // Can't move
            if (sizeof($this->dices) > 0) {
                $this->infoCantMove();
            }

            if ($this->doubles > 0) {
                // Double => again
                $this->requestThrowDices();
            } else {
                // Not double, next turn
                $this->turn();
            }
        }
    }

    /**
     * Dices
     */
    protected function requestThrowDices()
    {
        $this->throw_dices = true;
        $this->emit("dices", $this->turn->getId());
    }

    protected function onThrowDices()
    {
        if (!$this->throw_dices) {
            return false;
        }
        $this->throw_dices = false;

        $this->dices = [rand(1, 6), rand(1, 6)];
        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $this->turn .": throw dices ". $this->dices[0] ."," . $this->dices[1]. " (Doubles: ". $this->doubles .")");
        $this->infoDices();

        // Doubles
        if ($this->dices[0] == $this->dices[1]) {
            $this->doubles += 1;
        } else {
            $this->doubles = 0;
        }

        $this->process();
    }

    protected function infoDices()
    {
        $this->emit("info_dices", $this->dices);
    }

    /**
     * Moves
     */
    protected function requestMove($moves)
    {
        $this->make_move = true;
        $this->emit("move", array(
            "id" => $this->turn->getId(),
            "dices" => $this->dices,
            "moves" => $moves
        ));
    }

    protected function onChipMove($chip, $to)
    {
        if (!$this->make_move) {
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $this->turn .": Unrequested move ". $chip ." to: ". $to);
            return;
        }
    
        $kill = $this->board->check_kill($chip, $to);
        if (($dices = $this->board->move($this->turn, $chip, $to, $this->dices)) === false) {
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $this->turn .": Invalid move ". $chip ." to: ". $to);
            return;
        }
        
        $this->make_move = false;

        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $this->turn .": move ". $chip ." to ". $to);
        $this->infoMove($chip);

        if ($kill !== false) {
            $this->board->kill($kill);
            $this->infoDie($kill);
            $dices[] = 20;
        }

        $finish = $this->turn->get_color()->get_finish();
        if ($to == $finish) {
            $dices[] = 10;

            $winner = true;
            foreach ($this->turn->get_chips() as $c) {
                if ($c->get_position() != $finish) {
                    $winner = false;
                    break;
                }
            }

            if ($winner) {
                $this->logger->info(__FUNCTION__.":".__LINE__ .":". $this->turn .": won the game");
                return;
            }
        }

        $this->dices = $dices;
        $this->process();
    }
    
    protected function infoMove($chip)
    {
        $this->emit("info_move", array(
            "id"   => $this->turn->getId(),
            "chip" => $chip->getId(),
            "to"  => $chip->get_position()
        ));
    }

    protected function infoDie($chip)
    {
        // @TODO: Use ig
        foreach ($this->players as $player) {
            if ($player->get_color()->equals($chip->get_color())) {
                $targetPlayer = $player;
            }
        }
        if (!isset($targetPlayer)) {
            $this->logger->error(__FUNCTION__.":".__LINE__ .":". $this->turn .": Cannot find player of chip ". $chip);
            return;
        }
        
        $this->emit("info_die", array(
            "id"   => $targetPlayer->getId(),
            "chip" => $chip->getId()
        ));
    }


    /**
     * Game management
     */
    protected function onACK($player, $event)
    {
        $this->logger->info(__FUNCTION__.":".__LINE__ .":". $player .": ack ". $event);
        $this->acks[$event]->ack($player);
    }

    public function onPlayerAction($player, $data)
    {
        switch ($data['action']) {
            case "dices":
                if ($player !== $this->turn) {
                    $this->logger->error(__FUNCTION__.":".__LINE__ .":". $player .": Not his turn ". print_r($data, true));
                    return;
                }
                $this->onThrowDices();
                break;
            case "move":
                if ($player !== $this->turn) {
                    $this->logger->error(__FUNCTION__.":".__LINE__ .":". $player .": Not his turn ". print_r($data, true));
                    return;
                }
                if (
                    !isset($data["id"]) ||
                    !isset($data["to"]) ||
                    (!is_string($data["id"]) && !is_int($data["id"])) ||
                    (!is_string($data["to"]) && !is_int($data["to"]))
                ) {
                    $this->logger->error(__FUNCTION__.":".__LINE__ .":". $player .": Invalid move ". print_r($data, true));
                    return;
                }
                $this->onChipMove($player->get_chip($data["id"]), $data["to"]);
                break;
            case "ack":
                if (!isset($data["event"]) || !isset($this->acks[$data["event"]])) {
                    $this->logger->error(__FUNCTION__.":".__LINE__ .":". $player .": No ACK event ". print_r($data, true));
                    return;
                }
                $this->onACK($player, $data["event"]);
                break;
            default:
                return false;
                break;
        }
    }

    protected function infoPlay()
    {
        $this->emit("play", $this->gameSerialize());
    }

    protected function infoCantMove()
    {
        $this->emit("skip_move", $this->turn->getId());
    }

    protected function infoMaxDoubles()
    {
        $this->emit("skip_double", $this->turn->getId());
    }

    protected function start()
    {
        $this->assign_player_colors();
        $this->assign_player_chips();
        
        $this->board = new Board();
        
        // @TODO: First chip out?
        // $dices = $this->board->move($this->turn, $chip, $to, $this->dices);
        
        // @TODO: What if never ACK?
        $this->acks["play"] = new ACK($this->players, function () {
            $this->turn();
        });
        $this->infoPlay();
    }

    protected function finish()
    {
    }

    /**
     * Serialization
     */
    public function gameSerialize()
    {
        return array(
            "players" => $this->players->gameSerialize(),
            "turn" => $this->turn === false ? false : $this->turn->getId(),
            "dices" => $this->dices
        );
    }
}
