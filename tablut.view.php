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

        for ($x=0; $x<=10; $x++) {
            for ($y=0; $y<=10; $y++) {
                $this->page->insert_block("square", array(
                    'X' => $x,
                    'Y' => $y,
                    'LEFT' => round(($x - 1) * self::PX_SCALE),
                    'TOP' => round(($y - 1) * self::PX_SCALE),
                    'CSS_CLASSES' => $this->squareCssClasses($x, $y),
                    'TEXT' => $this->squareText($x, $y)
                ));
            }
        }
    }

    public function squareText($x, $y)
    {
        if (($y == 0 || $y == 10) && $x > 0 && $x < 10) {
            return chr(64 + $x);
        }
        if (($x == 0 || $x == 10) && $y > 0 && $y < 10) {
            return $y;
        }
        return '';
    }

    public function squareCssClasses($x, $y)
    {
        $cssClasses = 'square';
        if ($x != 0 && $x != 10 && $y != 0 && $y != 10) {
            if ($this->isFortress($x, $y)) {
                $cssClasses .= ' fortress';
            }
            if ($this->isThrone($x, $y)) {
                $cssClasses .= ' throne';
            }
            if ($this->isCornerSquare($x, $y)) {
                $cssClasses .= ' corner';
            }
        } else {
            $cssClasses = 'border';
            if ($x == 0) {
                $cssClasses .= ' border-left';
            }
            if ($x == 10) {
                $cssClasses .= ' border-right';
            }
            if ($y == 0) {
                $cssClasses .= ' border-top';
            }
            if ($y == 10) {
                $cssClasses .= ' border-bottom';
            }
        }
        return $cssClasses;
    }

    public function isThrone($x, $y)
    {
        return ($x == 5 && $y == 5);
    }

    public function isFortress($x, $y)
    {
        if ($this->game->gamestate->table_globals[100] == '1') {
            return $this->isCornerSquare($x, $y);
        }
        // Variant:
        return ($x == 1 && $y >= 4 && $y <= 6)
            || ($x == 2 && $y == 5)
            || ($x == 9 && $y >= 4 && $y <= 6)
            || ($x == 8 && $y == 5)
            || ($x >= 4 && $x <= 6 && $y == 1)
            || ($x == 5 && $y == 2)
            || ($x >= 4 && $x <= 6 && $y == 9)
            || ($x == 5 && $y == 8);
    }
    
    public function isCornerSquare($x, $y)
    {
        return ($x == 1 && $y == 1)
            || ($x == 1 && $y == 9)
            || ($x == 9 && $y == 1)
            || ($x == 9 && $y == 9);
    }
}
