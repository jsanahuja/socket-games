<?php

namespace Games\Games\Parchis;

use Games\Games\Parchis\Chip;

class Board
{
    protected $map;

    protected $last_moved;
    protected $tmp_bridge;
    protected $bridge;

    protected static $SECURE = array(
        5, 12, 17, 22, 29, 34, 39, 46, 51, 56, 63, 68

    );

    public static $FINALES = [76, 84, 92, 100];

    public function __construct()
    {
        $this->map = array();
        $this->bridge = array();
    }

    /**
     * Protected methods
     */
    protected function is_full($position)
    {
        return !in_array($position, self::$FINALES) &&
               isset($this->map[$position]) &&
               sizeof($this->map[$position]) == 2;
    }

    protected function is_bridged($position)
    {
        if (!$this->is_full($position)) {
            fwrite(STDERR, "-- $position Not full". PHP_EOL);
            return false;
        }

        $chips = array_values($this->map[$position]);
        return $chips[0]->get_color()->equals($chips[1]->get_color());
    }

    protected function is_secure($position)
    {
        return in_array($position, self::$SECURE) || $position >= 69;
    }

    protected function valid_dices($jumps, $dices)
    {
        if (in_array(10, $dices) && $jumps < 10) {
            return false;
        }
        if (in_array(20, $dices) && $jumps < 20) {
            return false;
        }
        return in_array($jumps, $dices) || $jumps == array_sum($dices);
    }

    protected function must_break_bridges($player, $dices)
    {
        if (sizeof($dices) < 2) {
            return false;
        }
        
        if ($dices[0] != $dices[1]) {
            return false;
        }
        
        foreach ($player->get_chips() as $chip) {
            $position = $chip->get_position();
            if (
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
            ) {
                return true;
            }
        }
        return false;
    }

    protected function must_take_chip_out($player, $dices)
    {
        if (!$this->valid_dices(5, $dices)) {
            return false;
        }

        $initial = $player->get_color()->get_initial();

        foreach ($player->get_chips() as $chip) {
            if (
                $chip->get_position() == -1 &&
                $this->physical_valid_move($chip, $initial, $dices)
            ) {
                return true;
            }
        }
        return false;
    }

    // @TODO: Remove debug
    protected function valid_move($player, $chip, $to, $dices)
    {
        fwrite(STDERR, "Move ".$chip->get_position()." -> ". $to . PHP_EOL);
        $color = $player->get_color();

        if (in_array($chip, $this->bridge)) {
            fwrite(STDERR, "- Chip is part of breaking bridge" . PHP_EOL);
            return false;
        }

        // Must break bridges
        $from = $chip->get_position();
        if ($this->must_break_bridges($player, $dices)) {
            if ($this->is_bridged($from)) {
                $jumps = $this->physical_valid_move($chip, $to, $dices);

                if ($jumps !== false) {
                    foreach ($this->map[$from] as $c) {
                        if (!$chip->equals($c)) {
                            $this->tmp_bridge = array($c);
                        }
                    }
                } else {
                    fwrite(STDERR, "- Can't break this bridge" . PHP_EOL);
                }
                
                return $jumps;
            } else {
                fwrite(STDERR, "- Must break a bridge " . PHP_EOL);
                return false;
            }
        } else {
            $this->tmp_bridge = array();

            // Must take chip out
            if ($this->must_take_chip_out($player, $dices)) {
                if ($from == -1 && $to == $color->get_initial()) {
                    return 5;
                } else {
                    fwrite(STDERR, "- Must take chip out: ". $color->get_initial() . PHP_EOL);
                    return false;
                }
            } else {
                return $this->physical_valid_move($chip, $to, $dices);
            }
        }
    }

    protected function physical_valid_move($chip, $to, $dices)
    {
        $position = $chip->get_position();
        $color = $chip->get_color();

        if ($position == -1 && $to == $color->get_initial()) {
            // Moving to initial

            // Not a 5.
            if (!$this->valid_dices(5, $dices)) {
                fwrite(STDERR, "-- Invalid dices to takeout". PHP_EOL);
                return false;
            }

            // Initial is blocked
            if ($this->is_bridged($to)) {
                foreach ($this->map[$to] as $c) {
                    if (!$c->get_color()->equals($color)) {
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
        } else {
            if ($this->is_full($to)) {
                fwrite(STDERR, "-- Target is full" . PHP_EOL);
                return false;
            }

            $jumps = 0;
            $max_jumps = array_sum($dices);

            do {
                $jumps += 1;
                $position = $color->get_next($position);

                if ($jumps > $max_jumps) {
                    fwrite(STDERR, "-- Max jumps reached" . PHP_EOL);
                    return false;
                }
                if ($this->is_bridged($position)) {
                    fwrite(STDERR, "-- Can't move because of a bridge" . PHP_EOL);
                    return false;
                }
            } while ($position != $to);

            if (!$this->valid_dices($jumps, $dices)) {
                return false;
            }
            return $jumps;
        }
    }

    protected function update($chip, $position)
    {
        $from = $chip->get_position();

        if ($from != -1) {
            unset($this->map[$from][$chip->get_uuid()]);
        }

        if ($position != -1) {
            if (!isset($this->map[$position])) {
                $this->map[$position] = array();
            }

            $this->map[$position][$chip->get_uuid()] = $chip;
        }

        $chip->set_position($position);
    }

    /**
     * Public methods
     */
    public function can_premove($player)
    {
        return (sizeof($this->get_moves($player, [1,1])) +
                sizeof($this->get_moves($player, [1,2])) +
                sizeof($this->get_moves($player, [1,3])) +
                sizeof($this->get_moves($player, [1,4])) +
                sizeof($this->get_moves($player, [1,5])) +
                sizeof($this->get_moves($player, [1,6])) +
                sizeof($this->get_moves($player, [2,2])) +
                sizeof($this->get_moves($player, [2,3])) +
                sizeof($this->get_moves($player, [2,4])) +
                sizeof($this->get_moves($player, [2,5])) +
                sizeof($this->get_moves($player, [2,6])) +
                sizeof($this->get_moves($player, [3,3])) +
                sizeof($this->get_moves($player, [3,4])) +
                sizeof($this->get_moves($player, [3,5])) +
                sizeof($this->get_moves($player, [3,6])) +
                sizeof($this->get_moves($player, [4,4])) +
                sizeof($this->get_moves($player, [4,5])) +
                sizeof($this->get_moves($player, [4,6])) +
                sizeof($this->get_moves($player, [5,5])) +
                sizeof($this->get_moves($player, [5,6])) +
                sizeof($this->get_moves($player, [6,6])) +
                sizeof($this->get_moves($player, [3,3]))
               ) > 0;
    }

    public function get_moves($player, $dices)
    {
        $moves = array();

        if (sizeof($dices) == 0) {
            return $moves;
        }

        // @TODO: Remove
        if (sizeof($dices) > 2) {
            throw new \Exception("More than 2 dices? Something is wrong:" . print_r($dices, true));
        }


        $jumps = $dices;
        if (sizeof($jumps) == 2) {
            $jumps[] = array_sum($jumps);
        }
        
        foreach ($player->get_chips() as $chip) {
            foreach ($jumps as $dice) {
                if (($to = $player->get_color()->jump($chip->get_position(), $dice)) === false) {
                    continue;
                }

                if ($to !== false && $this->valid_move($player, $chip, $to, $dices) !== false) {
                    $move = array($chip->getId(), $to);
                    if (!in_array($move, $moves)) {
                        $moves[] = $move;
                    }
                }
            }
        }

        return $moves;
    }

    public function move($player, $chip, $to, $dices)
    {
        $jumps = $this->valid_move($player, $chip, $to, $dices);

        if ($jumps !== false) {
            $this->update($chip, $to);
    
            // Bridge
            if (sizeof($this->tmp_bridge) > 0) {
                $this->bridge = $this->tmp_bridge;
            } else {
                $this->bridge = array();
            }
        
            // Dices
            if ($jumps == array_sum($dices)) {
                return array();
            }
                
            unset($dices[array_search($jumps, $dices)]);
            return array_values($dices);

            // Last move
            $this->last_moved = $chip;
        }
        return false;
    }

    public function check_kill($chip, $to)
    {
        $color = $chip->get_color();
        $initial = $color->get_initial();

        if ($this->is_secure($to)) {
            if ($to == $initial && $this->is_full($to)) {
                $toKill = false;
                foreach ($this->map[$to] as $target) {
                    if (!$target->get_color()->equals($color)) {
                        $toKill = $target;
                    }
                }
                return $toKill;
            }
        } elseif (isset($this->map[$to]) && sizeof($this->map[$to]) == 1) {
            $target = end($this->map[$to]);
            if (!$target->get_color()->equals($color)) {
                return $target;
            }
        }
        return false;
    }

    public function kill_last_moved()
    {
        if ($this->last_moved instanceof Chip && !$this->is_secure($this->last_moved->get_position())) {
            $this->kill($this->last_moved);
            return $this->last_moved;
        } else {
            return false;
        }
    }

    public function kill($chip)
    {
        $this->update($chip, -1);
    }
}
