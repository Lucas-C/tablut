<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * -----
  *
  * tablut.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */

require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');

use Functional as F;
use Tablut\Functional as HF;
use Tablut\GameSetup;
use Tablut\SQLHelper;

class Tablut extends Table
{
    const DOWNWARD_PLAYER_COLOR = '3b550c';

    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([]);
    }

    /**
     * @return string
     */
    protected function getGameName()
    {
        return "tablut";
    }

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = [])
    {
        $this->setupPlayers($players);
        $this->setupStats();
        $this->activeNextPlayer();
        $this->gamestate->setAllPlayersMultiactive();
    }

    private function setupStats()
    {
        $this->initStat('player', 'num_defeated_cards', 0);
    }

    /**
     * @param array $players
     */
    private function setupPlayers(array $players)
    {
        if (count($players) !== 2) {
            throw new InvalidArgumentException('Can only work with 2 players');
        }
    }

    /**
     * Gather all informations about current game situation (visible by the current player).
     * The method is called each time the game interface is displayed to a player, ie:
     *  - when the game starts
     *  - when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $playerId = (int) $this->getActivePlayerId();
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    public function getGameProgression()
    {
        $percent = 42;
        return (int) round(100 * $percent);
    }

    public function stNextPlay()
    {
        $playerId = (int) $this->getActivePlayerId();

        /*self::DbQuery(
            "UPDATE player SET turn_plays_remaining = turn_plays_remaining - 1 WHERE player_id = {$playerId}"
        );*/
        
        $this->gamestate->nextState('playAgain');
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////
    /**
     * @param int $squareId
     * @throws BgaUserException
     */
    public function moveTo(array $squareId)
    {
        $this->checkAction('moveTo');
        $this->move($this->getCurrentPlayerId(), $squareId); // should call $this->notifyAllPlayers(
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */
    public function zombieTurn($state, $activePlayerId)
    {
        switch ($state['name']) {
            default:
                throw new BgaSystemException("Unknown state for zombie {$state['name']}");
        }
        //$this->gamestate->updateMultiactiveOrNextState( '' );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    public function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            $sql = "ALTER TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            $sql = "CREATE TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        // Please add your future database scheme changes here
//
//
    }
}
