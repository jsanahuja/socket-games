<?php

namespace Games\Games\Parchis;

class Board{
    protected $map;

    protected $tmp_bridge;
    protected $bridge;

    protected static $secure = array(
        5, 12, 17, 22, 29, 34, 39, 46, 51, 56, 63, 68
    );

    public static $FINALES = [76, 84, 92, 100];

    public function __construct(){
        $this->map = array();
        $this->bridge = array();
    }

    /**
     * Protected methods
     */
    protected function is_full($position){
        return !in_array($position, self::$FINALES) &&
               isset($this->map[$position]) && 
               sizeof($this->map[$position]) == 2;
    }

    protected function is_bridged($position){
        if(!$this->is_full($position))
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
        
        if($dices[0] != $dices[1])
            return false;
        
        foreach($player->get_chips() as $chip){
            $position = $chip->get_position();
            if(
                $this->is_bridged($position) && (
                    $this->physical_valid_move(
                        $chip, 
                        $chip->get_color()->jump($position, $dices[0]), 
                        $dices
                    ) ||
                    $this->physical_valid_move(
                        $chip, 
                        $chip->get_color()->jump($position, array_sum($dices)), 
                        $dices
                    )
                )
            ){
                fwrite(STDERR, "YES: MUST BREAK BRIDGE" . PHP_EOL);
                return true;
            }
        }
        return false;
    }

    protected function must_take_chip_out($player, $dices){
        if(!$this->valid_dices(5, $dices))
            return false;

        $initial = $player->get_color()->get_initial();

        foreach($player->get_chips() as $chip){
            if(
                $chip->get_position() == -1 &&
                $this->physical_valid_move($chip, $initial, $dices)
            )
                return true;
        }
        return false;
    }

    // @TODO: Remove debug
    protected function valid_move($player, $chip, $to, $dices){
        fwrite(STDERR, "Move ".$chip->get_position()." -> ". $to . PHP_EOL);
        $color = $player->get_color();

        if(in_array($chip, $this->bridge)){
            fwrite(STDERR, "- Chip is part of breaking bridge" . PHP_EOL);
            return false;
        }

        // Must break bridges
        $from = $chip->get_position();
        if($this->must_break_bridges($player, $dices)){
            if($this->is_bridged($from)){
                $jumps = $this->physical_valid_move($chip, $to, $dices);

                if($jumps !== false){
                    foreach($this->map[$from] as $c){
                        if(!$chip->equals($c))
                            $this->tmp_bridge = array($c);
                    }
                }else{
                    fwrite(STDERR, "- Can't break this bridge" . PHP_EOL);
                }
                
                return $jumps;
            }else{
                fwrite(STDERR, "- Must break a bridge " . PHP_EOL);
                return false;
            }
        }else{
            $this->tmp_bridge = array();

            // Must take chip out
            if($this->must_take_chip_out($player, $dices)){
                if($from == -1 && $to == $color->get_initial()){
                    return 5;
                }else{
                    fwrite(STDERR, "- Must take chip out: ". $color->get_initial() . PHP_EOL);
                    return false;
                }
            }else{
                return $this->physical_valid_move($chip, $to, $dices);
            }
        }
    }

    public function physical_valid_move($chip, $to, $dices){
        $position = $chip->get_position();
        $color = $chip->get_color();

        if($position == -1 && $to == $color->get_initial()){
            // Moving to initial

            // Not a 5.
            if(!$this->valid_dices(5, $dices)){
                fwrite(STDERR, "-- Invalid dices to takeout". PHP_EOL);
                return false;
            }

            // Initial is blocked
            if($this->is_bridged($to)){
                foreach($this->map[$to] as $c){
                    if(!$c->get_color()->equals($color)){
                        // The block is not ours => kill
                        return 5;
                    }
                }
                fwrite(STDERR, "-- Initial is blocked". PHP_EOL);
                return false;
            }

            // Initial not blocked.
            fwrite(STDERR, "-- Initial is free". PHP_EOL);
            return 5;
        }else{
            if($this->is_full($to)){
                fwrite(STDERR, "-- Target is full" . PHP_EOL);
                return false;
            }

            $jumps = 0;
            $max_jumps = array_sum($dices);

            do{
                $jumps += 1;
                $position = $color->get_next($position);

                if($jumps > $max_jumps){
                    fwrite(STDERR, "-- Max jumps reached" . PHP_EOL);
                    return false;
                }
                if($this->is_bridged($position)){
                    fwrite(STDERR, "-- Can't move because of a bridge" . PHP_EOL);
                    return false;
                }
            }while($position != $to);

            if(!$this->valid_dices($jumps, $dices)){
                return false;
            }
            return $jumps;
        }
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
                if(($to = $player->get_color()->jump($chip->get_position(), $dice)) === false)
                    continue;

                print "----" . $chip->get_id() . " --> ". $to ." (". $dice .")". PHP_EOL;
                if($to !== false && $this->valid_move($player, $chip, $to, $dices) !== false){
                    print "OK". PHP_EOL;
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
            return array_values($dices);
        }
        return false;
    }

}