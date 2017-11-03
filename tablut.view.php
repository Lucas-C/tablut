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
        $players = $this->game->loadPlayersBasicInfos();

        // Spectator
        if (!isset($players[$currentPlayerId])) {
            $this->tpl['GAME_CONTAINER_CLASS'] = '';
            return;
        }

        if ($players[$currentPlayerId]['player_color'] !== Tablut::DOWNWARD_PLAYER_COLOR) {
            $this->tpl['GAME_CONTAINER_CLASS'] = 'viewing-as-upwards-player';
        } else {
            $this->tpl['GAME_CONTAINER_CLASS'] = '';
        }

        $this->page->insert_block('player_cards');
    }
}
