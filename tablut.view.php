<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * -----
 *
 * tablut.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in tablut_tablut.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */

require_once(APP_BASE_PATH . "view/common/game.view.php");

class view_tablut_tablut extends game_view  // @codingStandardsIgnoreLine
{
    const PX_SCALE = 60;

    public function getGameName()
    {
        return "tablut";
    }

    public function build_page($viewArgs)  // @codingStandardsIgnoreLine
    {
        global $g_user;
        $this->page->begin_block("tablut_tablut", "square");

        $currentPlayerId = (int) $g_user->get_id();

        for ($x=0; $x<=8; $x++) {
            for ($y=0; $y<=8; $y++) {
                $this->page->insert_block("square", array(
                    'X' => $x + 1,
                    'Y' => $y + 1,
                    'LEFT' => round($x * self::PX_SCALE),
                    'TOP' => round($y * self::PX_SCALE),
                    'EXTRA_CLASS' => ($this->isFortressSquare($x, $y) ? 'fortress' : '') . ' ' . ($this->isCornerSquare($x, $y) ? 'corner' : '')
                ));
            }
        }
    }

    public function isFortressSquare($x, $y)
    {
        if ($this->game->gamestate->table_globals[100] == 0 ) {
            return ($x == 4 && $y == 4)
            || ($x == 0 && $y >= 3 && $y <= 5)
            || ($x == 1 && $y == 4)
            || ($x == 8 && $y >= 3 && $y <= 5)
            || ($x == 7 && $y == 4)
            || ($x >= 3 && $x <= 5 && $y == 0)
            || ($x == 4 && $y == 1)
            || ($x >= 3 && $x <= 5 && $y == 8)
            || ($x == 4 && $y == 7);
        } else {
            return ($x == 4 && $y == 4) || $this->isCornerSquare($x, $y);
        }
    }
    
    public function isCornerSquare($x, $y)
    {
        return ($x == 0 && $y == 0)
            || ($x == 0 && $y == 8)
            || ($x == 8 && $y == 0)
            || ($x == 8 && $y == 8);
    }
}
