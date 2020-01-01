<?php

namespace Games\Games\Parchis;

class Board{
    private $map;

    private $tmp_bridge;
    private $bridge;

    public function __construct(){
        $this->map = array();

        $this->tmp_bridge = array();
        $this->bridge = array();
    }

    private function is_bridged($pos){
        if(!isset($this->map[$pos]))
            return false;
        if(sizeof($this->map[$pos]) != 2)
            return false;

        $c = array_values($this->map[$pos]);

        if($c[0]->get_color() == $c[1]->get_color())
            return true;
    }

    public function can_pre_move($player){
        $color = $player->get_color();
        $initial = $color->get_initial();

        foreach($player->get_chips() as $chip){
            $next = $color->get_next($chip->get_position());

            // @TODO: Fix premove check
            if(!$this->is_bridged($next)){
                return true;
            }
        }
        return false;
    }

    public function get_moves($player, $dices){
        echo "-- get_moves:" . PHP_EOL;
        $moves = array();

        if(sizeof($dices) == 0)
            return $moves;

        if(sizeof($dices) == 2)
            $dices[] = array_sum($dices);
        
        $color = $player->get_color();
        foreach($player->get_chips() as $chip){
            $pos = $chip->get_position();

            foreach($dices as $d){
                $to = $color->jump($pos, $d);
                if($to !== false && $this->valid_move($player, $chip, $to, $dices) !== false){
                    $moves[] = array($chip->get_id(), $to);
                }
            }
        }

        return $moves;
    }
    
    private function update($chip, $to){
        $id = $chip->get_id();
        $from = $chip->get_position();

        if($from != -1)
            unset($this->map[$from][$id]);

        if(!isset($this->map[$to]))
            $this->map[$to] = array();
        $this->map[$to][$id] = $chip;
        
        $chip->set_position($to);
    }

    public function validate_dices($req, $dices){
        return in_array($req, $dices) || $req == array_sum($dices);
    }

    public function substract_dices($jumps, $dices){
        if($jumps == array_sum($dices))
            return array();
        
        unset($dices[array_search($jumps, $dices)]);
        return $dices;
    }

    public function move($player, $chip, $to, $dices){
        $move = $this->valid_move($player, $chip, $to, $dices);

        if($move !== false){
            $this->update($chip, $to);
            if(sizeof($this->tmp_bridge) > 0){
                foreach($this->tmp_bridge as $c){
                    if($chip != $c)
                        $this->bridge = array($c);
                }
            }else{
                $this->bridge = array();
            }
        } 

        return $move;
    }

    public function valid_move($player, $chip, $to, $dices){
        echo "---- valid_move: ". $chip->get_position() . " -> ". $to . PHP_EOL;
        if(isset($this->map[$to]) && sizeof($this->map[$to]) == 2){
            echo "target is full". PHP_EOL;
            return false;
        }

        $from = $chip->get_position();
        $color = $chip->get_color();
        $initial = $color->get_initial();

        if(in_array($chip, $this->bridge)){
            echo "chip in a breaking bridge". PHP_EOL;
            return false;
        }

        if($this->must_break_bridges($player, $dices)){
            if(!$this->is_bridged($from)){
                echo "chip not in bridge that must be broken" . PHP_EOL;
                return false;
            }else{
                $this->tmp_bridge = $this->map[$from];
            }
        }else{
            $this->tmp_bridge = array();
        }


        if($this->must_take_chip_out($player, $dices) && ($from != -1 || $to != $initial)){
            echo "chip not at home. Must take out" . PHP_EOL;
            return false;
        }

        if($from == -1 && $to == $initial && $this->validate_dices(5, $dices)){
            echo "true! taking chip out" . PHP_EOL;
            return $this->substract_dices(5, $dices);
        }

        $jumps = 1;
        $max_jumps = array_sum($dices);
        $pos = $color->get_next($from);

        while($pos != $to){
            if($jumps > $max_jumps){
                echo "max jumps reached" . PHP_EOL;
                return false;
            }
            if($this->is_bridged($pos)){
                echo "there was a bridge..." . PHP_EOL;
                return false;
            }
            $jumps++;
            $pos = $color->get_next($pos);
        }

        if($this->validate_dices($jumps, $dices)){
            echo "true! lets f*cking go" . PHP_EOL;
            return $this->substract_dices($jumps, $dices);
        }
        echo "dices are not valid...". PHP_EOL;
        return false;
    }

    public function must_take_chip_out($player, $dices){
        if(!$this->validate_dices(5, $dices))
            return false;

        $initial = $player->get_color()->get_initial();
        $chips_initial = 0;
        $chips_home = 0;

        foreach($player->get_chips() as $chip){
            $p = $chip->get_position();
            if($p == -1)
                $chips_home++;
            else if($p == $initial)
                $chips_initial++;
        }

        return $chips_home > 0 && $chips_initial < 2;
    }

    public function must_break_bridges($player, $dices){
        if(sizeof($dices) < 2)
            return false;

        $dices = array_values($dices);
        if($dices[0] == $dices[1]){
            foreach($player->get_chips() as $chip){
                if($this->is_bridged($chip->get_position()))
                    return true;
            }
        }
        return false;
    }
}