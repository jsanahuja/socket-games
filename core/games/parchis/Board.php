<?php

namespace Games\Games\Parchis;

class Board{
    private $map;

    private $tmp_bridge;
    private $bridge;

    private static $secure = array(
        5, 12, 17, 22, 29, 34, 39, 46, 51, 56, 63, 68
    );

    public function __construct(){
        $this->map = array();
        $this->bridge = array();
    }

    /**
     * Protected methods
     */
    protected function is_bridged($position){
        if(!isset($this->map[$position]) || sizeof($this->map[$position]) != 2)
            return false;

        $chips = array_values($this->map[$position]);
        return $chips[0]->get_color()->equals($chips[1]->get_color());
    }

    protected function valid_dices($jumps, $dices){
        return in_array($jumps, $dices) || $jumps == array_sum($dices);
    }

    protected function must_break_bridges($player, $dices){
        if(sizeof($dices) < 2)
            return false;
        
        $dices = array_values($dices);

        if($dices[0] == $dices[1])
            return false;
        
        foreach($player->get_chips() as $chip)
            if($this->is_bridged($chip->get_position()))
                return true;
    }

    protected function must_take_chip_out($player, $dices){
        if(!$this->valid_dices(5, $dices))
            return false;

        if(!$this->is_bridged($player->get_color()->get_initial()))
            return false;

        foreach($player->get_chips() as $chip){
            if($chip->get_position() == -1)
                return true;
        }
        return false;
    }

    // @TODO: Debug to fix issues
    protected function valid_move($player, $chip, $to, $dices){
        $color = $player->get_color();

        // Target is full
        if(isset($this->map[$to]) && sizeof($this->map[$to]) == 2){
            // Target is not initial.
            if($to != $color->get_initial()){
                return false;
            }

            $bridge_is_ours = true;
            foreach($this->map[$to] as $target_chip){
                if(!$target_chip->get_color()->equals($color)){
                    $bridge_is_ours = false;
                }
            }

            // Target is initial but the bridge is ours.
            if($bridge_is_ours)
                return false;
        }

        if(in_array($chip, $this->bridge)){
            return false;
        }

        // Must break bridges
        $from = $chip->get_position();
        if($this->must_break_bridges($player, $dices)){
            if(!$this->is_bridged($from)){
                return false;
            }else{
                // Bridge 
                foreach($this->map[$from] as $c){
                    if(!$chip->equals($c))
                        $this->tmp_bridge = array($c);
                }
            }
        }else{
            $this->tmp_bridge = array();

            // Must take chip out
            if($this->must_take_chip_out($player, $dices)){
                if($from == -1 && $to == $color->get_initial()){
                    return 5;
                }else{
                    return false;
                }
            }
        }

        $jumps = 1;
        $max_jumps = array_sum($dices);
        $position = $color->get_next($from);

        while($position != $to){
            if($jumps > $max_jumps){
                return false;
            }
            if($this->is_bridged($position)){
                return false;
            }

            $jumps++;
            $position = $color->get_next($position);
        }

        if(!$this->valid_dices($jumps, $dices)){
            return false;
        }

        return $jumps;
    }

    /**
     * Public methods
     */
    public function can_premove($player){
        return sizeof(
            $this->get_moves(
                $player, 
                array(1,1,1,1,1,1,1,1,1,1,1,1,5,3)
            )
        ) > 0;
    }

    public function get_moves($player, $dices){
        $moves = array();

        if(sizeof($dices) == 0)
            return $moves;

        if(sizeof($dices) == 2)
            $dices[] = array_sum($dices);
        
        foreach($player->get_chips() as $chip){
            foreach($dices as $dice){
                $to = $player->get_color()->jump($chip->get_position(), $dice);
                if($to !== false && $this->valid_move($player, $chip, $to, $dices) !== false){
                    $moves[] = array($chip->get_id(), $to);
                }
            }
        }

        return $moves;
    }

    public function move($player, $chip, $to, $dices){
        $jumps = $this->valid_move($player, $chip, $to, $dices);

        if($jumps !== false){

            $id = $chip->get_id();
            $from = $chip->get_position();
    
            if($from != -1){
                unset($this->map[$from][$id]);
            }
            if(!isset($this->map[$to])){
                $this->map[$to] = array();
            }
            $this->map[$to][$id] = $chip;
            $chip->set_position($to);
    
            // Bridge
            if(sizeof($this->tmp_bridge) > 0){
                $this->bridge = $this->tmp_bridge;
            }else{
                $this->bridge = array();
            }
        
            // Dices

            if($jumps == array_sum($dices))
                return array();
                
            unset($dices[array_search($jumps, $dices)]);
            return $dices;
        }
        return false;
    }

}