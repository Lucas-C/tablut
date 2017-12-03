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

require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');  // @codingStandardsIgnoreLine

use Functional as F;
use Tablut\Functional as HF;
use Tablut\GameSetup;
use Tablut\SQLHelper;

class Tablut extends Table
{
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
        if (count($players) !== 2) {
            throw new InvalidArgumentException('Can only work with 2 players');
        }
        $this->setupPlayers($players);
        $this->setupBoard($players);
        $this->setupStats();
        $this->activeNextPlayer();
    }

    /**
     * @param array $players
     */
    private function setupPlayers(array $players)
    {
        $default_color = array( "000000", "ffffff" );
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_color);
            $playerName = addslashes($player['player_name']);
            $playerAvatar = addslashes($player['player_avatar']);
            $values[] = "('$player_id','$color','$player[player_canal]','$playerName','$playerAvatar')";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
        self::reloadPlayersBasicInfos();
    }

    private function setupBoard(array $players)
    {
        $player1 = "'".array_keys($players)[0]."'"; /* Not King for test */
        $player2 = "'".array_keys($players)[1]."'"; /* King fir test */ 
        
        /* initialize the player 1 piece*/
        $sql = "INSERT INTO board (board_x,board_y,board_king,board_wall,board_player) VALUES  ('4', '1', '0', '1', $player1), ('5', '1', '0', '1', $player1), ('6', '1', '0', '1', $player1), ('5', '2', '0', '1', $player1)" ;
        self::DbQuery($sql);
        $sql = "INSERT INTO board (board_x,board_y,board_king,board_wall,board_player) VALUES  ('4', '9', '0', '1', $player1), ('5', '9', '0', '1', $player1), ('6', '9', '0', '1', $player1), ('5', '8', '0', '1', $player1)" ;
        self::DbQuery($sql);
        $sql = "INSERT INTO board (board_x,board_y,board_king,board_wall,board_player) VALUES  ('1', '4', '0', '1', $player1), ('1', '5', '0', '1', $player1), ('1', '6', '0', '1', $player1), ('2', '5', '0', '1', $player1)" ;
        self::DbQuery($sql);
        $sql = "INSERT INTO board (board_x,board_y,board_king,board_wall,board_player) VALUES  ('9', '4', '0', '1', $player1), ('9', '5', '0', '1', $player1), ('9', '6', '0', '1', $player1), ('8', '5', '0', '1', $player1)" ;
        self::DbQuery($sql);
		
        /* initialize the player 2 piece*/
        $sql = "INSERT INTO board (board_x,board_y,board_king,board_wall,board_player) VALUES  ('5', '5', '1', '1', $player2)" ;
        self::DbQuery($sql);
        $sql = "INSERT INTO board (board_x,board_y,board_king,board_wall,board_player)  VALUES  ('3', '5', '0', '0', $player2), ('4', '5', '0', '0', $player2), ('6', '5', '0', '0', $player2), ('7', '5', '0', '0', $player2)" ;
        self::DbQuery($sql);
        $sql = "INSERT INTO board (board_x,board_y,board_king,board_wall,board_player)  VALUES  ('5', '3', '0', '0', $player2), ('5', '4', '0', '0', $player2), ('5', '6', '0', '0', $player2), ('5', '7', '0', '0', $player2)" ;
        self::DbQuery($sql);
        
        /* Initialize the limit winning game */
        $sql = "INSERT INTO board (board_x,board_y,board_limitWin) VALUES  ('1', '1', '1'), ('1', '2', '1'), ('1', '3', '1'), ('1', '7', '1'), ('1', '8', '1'), ('1', '9', '1')" ;
        self::DbQuery($sql);
        $sql = "INSERT INTO board (board_x,board_y,board_limitWin) VALUES  ('9', '1', '1'), ('9', '2', '1'), ('9', '3', '1'), ('9', '7', '1'), ('9', '8', '1'), ('9', '9', '1')" ;
        self::DbQuery($sql);
        $sql = "INSERT INTO board (board_x,board_y,board_limitWin) VALUES  ('2', '1', '1'), ('3', '1', '1'), ('7', '1', '1'), ('8', '1', '1')" ;
        self::DbQuery($sql);
        $sql = "INSERT INTO board (board_x,board_y,board_limitWin) VALUES  ('2', '9', '1'), ('3', '9', '1'), ('7', '9', '1'), ('8', '9', '1')" ;
        self::DbQuery($sql);

	}

    private function setupStats()
    {
        $this->initStat('table', 'turns_number', 0);
        $this->initStat('player', 'turns_number', 0);
    }


    /**
     * Gather all informations about current game situation (visible by the current player).
     * The method is called each time the game interface is displayed to a player, ie:
     *  - when the game starts
     *  - when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array( 'players' => array() );

        // Add players specific infos
        $sql = "SELECT player_id id, player_score score ";
        $sql .= "FROM player ";
        $sql .= "WHERE 1 ";
        $dbres = self::DbQuery($sql);
        while ($player = mysql_fetch_assoc($dbres)) {
            $result['players'][ $player['id'] ] = $player;
        }

        // Get reversi board disc
        $result['board'] = self::getObjectListFromDB("SELECT board_x x, board_y y, board_player player, board_king king
                                                      FROM board
                                                      WHERE board_player IS NOT NULL");

        return $result;
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
        // Game progression: get the number of free squares
        // (number of free squares goes from 60 to 0
        $freeSquare = self::getUniqueValueFromDb("SELECT COUNT( board_x ) FROM board WHERE board_player IS NULL");

        return round(( 60-$freeSquare )/60*100);
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////
    /**
     * @param int $fromSquareId
     * @param int $toSquareId
     * @throws BgaUserException
     */
    public function move(int $fromSquareId, int $toSquareId)
    {
        $this->checkAction('moveTo');
        // should call $this->notifyAllPlayers(
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    public function argPlayerTurn()
    {
        return array(
        );
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state reactions   (reactions to game planned states from state machine
////////////

    public function stNextPlayer()
    {
        $next_player_id = self::activeNextPlayer();

        //$this->gamestate->nextState( 'endGame' );
        //$this->gamestate->nextState( 'cantPlay' );
        $this->gamestate->nextState('nextTurn');
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
        if ($state['name'] == 'playerTurn') {
            $this->gamestate->nextState("zombiePass");
        } else {
            throw new feException("Zombie mode not supported at this game state:".$state['name']);
        }
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
