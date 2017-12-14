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
        return 'tablut';
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
        $default_color = array('000000', 'ffffff');
        $sql = 'INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES';
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

        /* Initialize all the board */
        $sql_values = array();
        $sql = 'INSERT INTO board (board_x, board_y, board_wall) VALUES ';
        for ($x=1; $x<=9; $x++) {
            for ($y=1; $y<=9; $y++) {
                if ($x==9 and $y==9) {
                    $sql .= "('$x', '$y', NULL) ";
                } else {
                    $sql .= "('$x', '$y', NULL), ";
                }
            }
        }
        self::DbQuery($sql);

        $player0 = array_keys($players)[0];
        $player1 = array_keys($players)[1];

        /* Initialize the player 0 pieces */
        self::DbQuery("UPDATE board SET board_player='$player0', board_wall='1' WHERE ( board_x, board_y) IN (('4','1'), ('5','1'), ('6','1'), ('5','2') )");
        self::DbQuery("UPDATE board SET board_player='$player0', board_wall='1' WHERE ( board_x, board_y) IN (('4','9'), ('5','9'), ('6','9'), ('5','8') )");
        self::DbQuery("UPDATE board SET board_player='$player0', board_wall='1' WHERE ( board_x, board_y) IN (('1','4'), ('1','5'), ('1','6'), ('2','5') )");
        self::DbQuery("UPDATE board SET board_player='$player0', board_wall='1' WHERE ( board_x, board_y) IN (('9','4'), ('9','5'), ('9','6'), ('8','5') )");

        /* Initialize the player 1 pieces */
        self::DbQuery("UPDATE board SET board_player='$player1', board_wall='1', board_king='1' WHERE ( board_x, board_y) IN (('5','5'))");
        self::DbQuery("UPDATE board SET board_player='$player1' WHERE ( board_x, board_y) IN (('3','5'), ('4','5'), ('6','5'), ('7','5'))");
        self::DbQuery("UPDATE board SET board_player='$player1' WHERE ( board_x, board_y) IN (('5','3'), ('5','4'), ('5','6'), ('5','7'))");

        /* Initialize the limit winning game */
        self::DbQuery("UPDATE board SET board_limitWin='1' WHERE ( board_x, board_y) IN (('1','1'), ('1','2'), ('1','3'), ('1','7'), ('1','8'), ('1','9'))");
        self::DbQuery("UPDATE board SET board_limitWin='1' WHERE ( board_x, board_y) IN (('9','1'), ('9','2'), ('9','3'), ('9','7'), ('9','8'), ('9','9'))");
        self::DbQuery("UPDATE board SET board_limitWin='1' WHERE ( board_x, board_y) IN (('2','1'), ('3','1'), ('7','1'), ('8','1'))");
        self::DbQuery("UPDATE board SET board_limitWin='1' WHERE ( board_x, board_y) IN (('2','9'), ('3','9'), ('7','9'), ('8','9'))");
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
        while ($player = self::DbQuery('SELECT player_id id, player_score score FROM player')->fetch_assoc()) {
            $result['players'][ $player['id'] ] = $player;
        }

        // Get reversi board disc
        $result['board'] = self::getObjectListFromDB('SELECT
                                                          board_x x,
                                                          board_y y,
                                                          board_player player,
                                                          board_king king,
                                                          board_wall wall,
                                                          board_limitWin WinPosition
                                                      FROM board');

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
        $freeSquare = self::getUniqueValueFromDb('SELECT COUNT( board_x ) FROM board WHERE board_player IS NULL');

        return round(( 60-$freeSquare )/60*100);
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////
    /**
     * @param int $fromDiscId
     * @param int $toSquareId
     * @throws BgaUserException
     */
    public function move(string $fromDiscId, string $toSquareId)
    {
        $this->checkAction('move'); // Check that this state change is possible

        $fromDiscPos = explode('_', $fromDiscId);
        $fromX = $fromDiscPos[1];
        $fromY = $fromDiscPos[2];
        $toSquarePos = explode('_', $toSquareId);
        $toX = $toSquarePos[1];
        $toY = $toSquarePos[2];

        $srcPawnFromDb = self::DbQuery("SELECT board_player, board_king FROM board WHERE board_x = $fromX AND board_y = $fromY")->fetch_assoc();
        $pawnPlayerId = (int) $srcPawnFromDb['board_player'];
        $pawnBoardKing = $srcPawnFromDb['board_king'] ? "'1'" : 'NULL';
        if (self::getActivePlayerId() != $pawnPlayerId) {
            throw new feException("This pawn belongs to your opponent: pawnPlayerId=$pawnPlayerId | pawnBoardKing=$pawnBoardKing");
        }

        $dstSquareFromDb = self::DbQuery("SELECT board_player, board_wall FROM board WHERE board_x = $toX AND board_y = $toY")->fetch_assoc();
        if ($dstSquareFromDb['board_wall'] != null) {
            throw new feException("Cannot move onto a wall");
        }
        if ($dstSquareFromDb['board_player'] != null) {
            throw new feException("Cannot move onto another pawn");
        }

        self::DbQuery("UPDATE board SET board_player=NULL,            board_king=NULL           WHERE board_x = $fromX AND board_y = $fromY");
        self::DbQuery("UPDATE board SET board_player='$pawnPlayerId', board_king=$pawnBoardKing WHERE board_x = $toX   AND board_y = $toY");

        self::notifyAllPlayers('pawnMoved', clienttranslate('${player_name} moves a pawn'), array(
            'player_id' => $pawnPlayerId,
            'player_name' => self::getActivePlayerName(),
            'fromDiscId' => $fromDiscId,
            'toSquareId' => $toSquareId,
            'gamedatas' => $this->getAllDatas()
        ));

        // Send another notif if a pawn was eaten

        $this->gamestate->nextState('move');
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
        $activePLayer = self::getActivePlayerId();        
        $next_player_id = self::activeNextPlayer();


        $WinKing = self::DbQuery("SELECT board_limitWin FROM board WHERE board_king='1' ")->fetch_assoc()['board_limitWin'];        
        $MoscovitWin = self::DbQuery("SELECT board_x FROM board WHERE board_king='1' ")->fetch_assoc()['board_x'];
        if( $MoscovitWin == NULL )
        {
            self::DbQuery("UPDATE player SET player_score='2' WHERE player_id='$ActivePLayer'");
            $this->gamestate->nextState( 'endGame' );
        }
        else if( $WinKing == '1' ) 
        {
            self::DbQuery("UPDATE player SET player_score='1' WHERE player_id='$ActivePLayer'");
            $this->gamestate->nextState( 'endGame' );
        }
        else{
            $this->gamestate->nextState('nextTurn');
        }
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
            $this->gamestate->nextState('zombiePass');
        } else {
            throw new feException('Zombie mode not supported at this game state:'.$state['name']);
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
