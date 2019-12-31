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
        if(sizeof($this->map[$to]) != 2)
            return false;

        $c = array_values($this->map[$to]);

        if($c[0]->get_color() == $c[1]->get_color())
            return true;
    }

    public function can_pre_move($player){
        $color = $player->get_color();
        $initial = $color->get_initial();
        foreach($player->get_chips() as $chip){
            $next = $color->get_next($chip->get_position());

            if($initial != $next && !$this->is_bridged($next)){
                return true;
            }
        }
        return false;
    }

    public function get_moves($player, $dices){
        $moves = array();
        
        $color = $player->get_color();
        $dices[] = array_sum($dices);
        foreach($player->get_chips() as $chip){
            $pos = $chip->get_position();

            foreach($dices as $d){
                if($this->valid_move($chip, $color->jump($pos, $d), array($d))){
                    $moves[] = array($chip->get_id(), $to)
                }
            }
        }

        return $moves;
    }
    
    private function update($chip, $to){
        $id = $chi->get_id();
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
        if($req == array_sum($dices))
            return array();
        
        unset($dices[array_search($jumps, $dices)]);
        return $dices;
    }

    public function move($player, $chip, $to, $dices){
        $move = $this->valid_move($player, $chip, $to, $dices);

        if($move !== false){
            $this->update($chip, $to);
        }else{
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
        if(isset($this->map[$to]) && sizeof($this->map[$to]) == 2)
            return false;
        
        $from = $chip->get_position();
        $color = $chip->get_color();
        $initial = $color->get_initial();

        if(in_array($chip, $this->bridge)
            return false;

        if($this->must_break_bridges($player, $dices)){
            if(!$this->is_bridged($from)){
                return false;
            }else{
                $this->tmp_bridge = $this->map[$from];
            }
        }else{
            $this->tmp_bridge = array();
        }

        if($this->must_take_chip_out($player) && $from != -1 || $to != $initial){
            return false;
        }

        if($from == -1 && $to == $initial && $this->validate_dices(5, $dices)){
            return substract_dices(5, $dices);
        }

        $jumps = 1;
        $max_jumps = array_sum($dices);
        $pos = $color->get_next($from);

        while($pos != $to){
            if($jumps > $max_jumps)
                return false;
            if($this->is_bridged($pos))
                return false;
            $jumps++;
            $pos = $color->get_next($pos);
        }

        if($this->validate_dices($jumps, $dices))
            return substract_dices($jumps, $dices);
        return false;
    }

    public function must_take_chip_out($player){
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
        if(sizeof($dices) != 2)
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