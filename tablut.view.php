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

/**
 * @property Tablut $game
 */
class view_tablut_tablut extends game_view
{
    public function getGameName()
    {
        return "tablut";
    }

    public function build_page($viewArgs)
    {
        global $g_user;
        $this->page->begin_block("tablut_tablut", "player_cards");

        $currentPlayerId = (int) $g_user->get_id();

        $hor_scale = 64.8;
        $ver_scale = 64.4;
        for( $x=1; $x<=8; $x++ )
        {
            for( $y=1; $y<=8; $y++ )
            {
                $this->page->insert_block( "square", array(
                    'X' => $x,
                    'Y' => $y,
                    'LEFT' => round( ($x-1)*$hor_scale+10 ),
                    'TOP' => round( ($y-1)*$ver_scale+7 )
                ) );
            }        
        }
    }
}
