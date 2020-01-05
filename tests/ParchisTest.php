<?php

namespace Games\Tests;

use PHPUnit\Framework\TestCase;

use Games\Games\Parchis\Board;
use Games\Games\Parchis\Chip;
use Games\Games\Parchis\Player;
use Games\Games\Parchis\Color;

class BoardMock extends Board
{
    public function virtual_move($chip, $to)
    {
        $id = $chip->get_id();
        $from = $chip->get_position();

        if ($from != -1) {
            unset($this->map[$from][$chip->get_uuid()]);
        }

        if (!isset($this->map[$to])) {
            $this->map[$to] = array();
        }
        
        $this->map[$to][$chip->get_uuid()] = $chip;
        $chip->set_position($to);
    }

    public function public_valid_move($player, $chip, $to, $dices)
    {
        return $this->valid_move($player, $chip, $to, $dices);
    }
}

/**
 * Test class
 */
class BoardTest extends TestCase
{
    protected $players;
    protected $board;

    // public static function setUpBeforeClass(){}

    protected function setUp() : void
    {
        $this->players = array();

        $colors = [
            Color::$YELLOW,
            Color::$BLUE,
            Color::$RED,
            Color::$GREEN
        ];

        for ($i = 0; $i < 4; $i++) {
            $player = new Player($i, "User". $i, null);
            $player->set_color(new Color(... $colors[$i]));

            for ($j = 0; $j < 4; $j++) {
                $player->add_chip(new Chip($j, $player->get_color()));
            }
            $this->players[$i] = $player;
        }

        $this->board = new BoardMock();
    }


    /** @test */
    public function test_valid_move()
    {
        $this->board->virtual_move($this->players[0]->get_chip(0), 5);
        $this->board->virtual_move($this->players[0]->get_chip(1), 5);
        $this->board->virtual_move($this->players[0]->get_chip(2), 23);
        
        $this->assertNotEquals(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(2),
                $this->players[0]->get_color()->jump(
                    $this->players[0]->get_chip(2)->get_position(),
                    5
                ),
                [5,6]
            ),
            false
        );
        $this->assertNotEquals(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(2),
                $this->players[0]->get_color()->jump(
                    $this->players[0]->get_chip(2)->get_position(),
                    6
                ),
                [5,6]
            ),
            false
        );
    }

    /** @test */
    public function test_valid_move2()
    {
        $this->board->virtual_move($this->players[0]->get_chip(0), 23);
        $this->board->virtual_move($this->players[0]->get_chip(1), 23);
        
        $this->assertFalse(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(2),
                $this->players[0]->get_color()->get_initial(),
                [5,5]
            )
        );
        $this->assertFalse(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(3),
                $this->players[0]->get_color()->get_initial(),
                [5,5]
            )
        );
        $this->assertNotEquals(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(1),
                $this->players[0]->get_color()->jump(
                    $this->players[0]->get_chip(1)->get_position(),
                    5
                ),
                [5,5]
            ),
            false
        );

        // Bidge makes our bridge cant be broken
        $this->board->virtual_move($this->players[1]->get_chip(0), 24);
        $this->board->virtual_move($this->players[1]->get_chip(1), 24);

        
        $this->assertNotEquals(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(2),
                $this->players[0]->get_color()->get_initial(),
                [5,5]
            ),
            false
        );
        $this->assertNotEquals(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(3),
                $this->players[0]->get_color()->get_initial(),
                [5,5]
            ),
            false
        );
        $this->assertFalse(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(1),
                $this->players[0]->get_color()->jump(
                    $this->players[0]->get_chip(1)->get_position(),
                    5
                ),
                [5,5]
            )
        );
    }

    /** @test */
    public function test_valid_move3()
    {
        $this->board->virtual_move($this->players[0]->get_chip(0), 5);
        $this->board->virtual_move($this->players[0]->get_chip(1), 5);
        
        // Bidge right after ours, which is at init
        $this->board->virtual_move($this->players[1]->get_chip(0), 6);
        $this->board->virtual_move($this->players[1]->get_chip(1), 6);

        $this->assertFalse(
            $this->board->can_premove($this->players[0])
        );
        $this->assertFalse(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(1),
                $this->players[0]->get_color()->jump(
                    $this->players[0]->get_chip(1)->get_position(),
                    5
                ),
                [5,5]
            )
        );
        $this->assertFalse(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(0),
                $this->players[0]->get_color()->jump(
                    $this->players[0]->get_chip(0)->get_position(),
                    5
                ),
                [5,5]
            )
        );
        $this->assertFalse(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(2),
                $this->players[0]->get_color()->get_initial(),
                [5,5]
            ),
            false
        );
        $this->assertFalse(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(3),
                $this->players[0]->get_color()->get_initial(),
                [5,5]
            ),
            false
        );
    }

    /** @test */
    public function test_valid_move4()
    {
        $this->board->virtual_move($this->players[0]->get_chip(0), 5);
        $this->board->virtual_move($this->players[0]->get_chip(1), 5);

        // Can break bridge
        $this->assertNotEquals(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(0),
                $this->players[0]->get_color()->jump(
                    $this->players[0]->get_chip(0)->get_position(),
                    5
                ),
                [5,5]
            ),
            false
        );
        $this->assertNotEquals(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(1),
                $this->players[0]->get_color()->jump(
                    $this->players[0]->get_chip(1)->get_position(),
                    5
                ),
                [5,5]
            ),
            false
        );
        // Cant take chip out
        $this->assertFalse(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(2),
                $this->players[0]->get_color()->get_initial(),
                [5,5]
            )
        );
        $this->assertFalse(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(3),
                $this->players[0]->get_color()->get_initial(),
                [5,5]
            )
        );

        // -- Breaking bridge --
        $this->assertEquals(
            $this->board->move(
                $this->players[0],
                $this->players[0]->get_chip(0),
                $this->players[0]->get_color()->jump(
                    $this->players[0]->get_chip(0)->get_position(),
                    5
                ),
                [5,5]
            ),
            [5]
        );

        // Can't move again => must take chip out
        $this->assertFalse(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(0),
                $this->players[0]->get_color()->jump(
                    $this->players[0]->get_chip(0)->get_position(),
                    5
                ),
                [5]
            )
        );
        
        // Cant move => is in breaking bridge
        $this->assertFalse(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(1),
                $this->players[0]->get_color()->jump(
                    $this->players[0]->get_chip(1)->get_position(),
                    5
                ),
                [5]
            )
        );
        // Can take chip out
        $this->assertNotEquals(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(2),
                $this->players[0]->get_color()->get_initial(),
                [5]
            ),
            false
        );
        $this->assertNotEquals(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(3),
                $this->players[0]->get_color()->get_initial(),
                [5]
            ),
            false
        );

        // -- Virtually tanking chips out --
        $this->board->virtual_move($this->players[0]->get_chip(2), 20);
        $this->board->virtual_move($this->players[0]->get_chip(3), 30);

        // Can move again because its the chip that broke bridge
        $this->assertNotEquals(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(0),
                $this->players[0]->get_color()->jump(
                    $this->players[0]->get_chip(0)->get_position(),
                    5
                ),
                [5]
            ),
            false
        );
        // Cant move => is in breaking bridge
        $this->assertFalse(
            $this->board->public_valid_move(
                $this->players[0],
                $this->players[0]->get_chip(1),
                $this->players[0]->get_color()->jump(
                    $this->players[0]->get_chip(1)->get_position(),
                    5
                ),
                [5]
            )
        );
    }
    
    /** @test */
    public function test_get_moves()
    {
        $this->board->virtual_move($this->players[2]->get_chip(0), 45);
        
        $this->assertEquals(
            $this->board->get_moves(
                $this->players[2],
                [5,5]
            ),
            [[1,39], [2,39], [3,39]]
        );
    }
}
