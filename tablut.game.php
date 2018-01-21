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
        $dbres = self::DbQuery('SELECT player_id id, player_score score FROM player');
        while ($player = mysql_fetch_assoc($dbres)) {
            $result['players'][ $player['id'] ] = $player;
        }

        // Get reversi board disc
        $result['board'] = self::getObjectListFromDB('SELECT
                                                          board_x x,
                                                          board_y y,
                                                          board_player player,
                                                          board_king king,
                                                          board_wall wall,
                                                          board_limitWin limitWin
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
        $fromX = (int) $fromDiscPos[1];
        $fromY = (int) $fromDiscPos[2];
        $toSquarePos = explode('_', $toSquareId);
        $toX = (int) $toSquarePos[1];
        $toY = (int) $toSquarePos[2];

		// pawn player identification
        $srcPawnFromDb = $this->dbPawnAt($fromX, $fromY);
        $pawnPlayerId = (int) $srcPawnFromDb['board_player'];
        $pawnIsKing = $srcPawnFromDb['board_king'] ? "'1'" : 'NULL';
		$pawnIsOnWall = $srcPawnFromDb['board_wall'] ? true : false ;
        
        // ------------------
        // reject play
        // ------------------
        
        $RejectMove = false;
        // reject if a pawn is present
        $dstSquareFromDb = self::DbQuery("SELECT board_player, board_wall FROM board WHERE board_x = $toX AND board_y = $toY")->fetch_assoc();
        if ($dstSquareFromDb['board_player'] != NULL) {
            throw new feException("Cannot move onto another pawn");
        }
        
        // reject diagonal move
        if ($toX != $fromX && $toY != $fromY ) {
            throw new feException("Cannot move on diagonal");
        }
        
        // throw an exception if is not in same column without pawn or wall between the disc to the final position
        if ( $toX == $fromX ) {
            if ( $fromY < $toY ) {
                $dbres_asc  = self::DbQuery("SELECT board_y posY, board_wall wall_present, board_player player_present FROM board WHERE board_x = $toX ORDER BY board_y ASC");
                // Loop on each position between the start position to the end position and verify that no wall and no pawn
                // specific case for down of the wall 
                while ($Column = mysql_fetch_assoc ($dbres_asc) ) {
                    if ( $fromY < $Column['posY'] &&  $Column['posY'] <= $toY ) {
                        ///////////////////
                        // dbg
                        //self::notifyAllPlayers('moveposible', "test", array(
                        //'column' => $Column,
                        //'IsWall' => $pawnIsOnWall,
                        //'king' => $pawnIsKing,
                        //'player' => $pawnPlayerId,
                        //'egal' => $pawnIsOnWall == true
                        //));
                        // End DBG
                        ///////////////////
                        if( $pawnIsOnWall ){
                            ///////////////////
                            // dbg
                            //self::notifyAllPlayers('moveposible2', "test", array(
                            //'IsWall' => $pawnIsOnWall
                            //));
                            //// End DBG
                            ///////////////////                        
                            if ($Column['wall_present'] == null ) {
                                $pawnIsOnWall = false;
                            }
                            if ($Column['player_present'] != null ) {
                                $RejectMove = true;
                            }
                            
                        } else {
                            if ($Column['wall_present'] != null || $Column['player_present'] != null ) {
                                $RejectMove = true;
								///////////////////
								// dbg
                                //self::notifyAllPlayers('Error', "test", array(
                                //    'Xmove' => true, 
                                //    'wall_present' => $Column['wall_present'],
                                //    'player_p' => $Column['player_present'] 
                                //    ));
								// End DBG
								///////////////////
                            }
                        }
                    }
                    
                } 
            } else {
                $dbres_desc = self::DbQuery("SELECT board_y posY, board_wall wall_present, board_player player_present FROM board WHERE board_x = $toX ORDER BY board_y DESC");
                // Loop on each position between the start position to the end position and verify that no wall and no pawn
                // specific case for down of the wall 
                while ($Column = mysql_fetch_assoc($dbres_desc) ) {
                    if ( $Column['posY'] < $fromY &&  $Column['posY'] >= $toY ) {
                        ///////////////////
                        // dbg
                        //self::notifyAllPlayers('moveposible', "test", array(
                        //'column' => $Column,
                        //'IsWall' => $pawnIsOnWall,
                        //'king' => $pawnIsKing,
                        //'player' => $pawnPlayerId,
                        //'egal' => $pawnIsOnWall == "1"
                        //));
                        // End DBG
                        ///////////////////
                        if( $pawnIsOnWall ){
                            ///////////////////
                            // dbg
                            //self::notifyAllPlayers('moveposible2', "test", array(
                            //'IsWall' => $pawnIsOnWall
                            //));
                            // End DBG
                            ///////////////////  
                            if ($Column['wall_present'] == null ) {
                                $pawnIsOnWall = false;
                            }
                            if ($Column['player_present'] != null ) {
                                $RejectMove = true;
                            }
                            
                        } else {
                            if ($Column['wall_present'] != null || $Column['player_present'] != null) {
                                $RejectMove = true;
								///////////////////
								// dbg
                                //self::notifyAllPlayers('Error', "test", array(
                                //    'Xmove2' => true, 
                                //    'wall_present' => $Column['wall_present'],
                                //    'player_p' => $Column['player_present'] 
                                //    ));
								// End DBG
								///////////////////
                            }
                        }
                    }
                }
            }
        } else {
            // $toY == $fromY 
            if ( $fromX < $toX ) {
                // Loop on each position between the start position to the end position and verify that no wall and no pawn
                // specific case for down of the wall 
                $dbres_asc  = self::DbQuery("SELECT board_X posX, board_wall wall_present, board_player player_present FROM board WHERE board_y = $toY ORDER BY board_y ASC");
                while ($row = mysql_fetch_assoc ($dbres_asc) ) {
                    if ( $fromX < $row['posX'] &&  $row['posX'] <= $toX ) {
                        if( $pawnIsOnWall ){
                            if ($row['wall_present'] == null ) {
                                $pawnIsOnWall = false;
                            }
                            if ($row['player_present'] != null ) {
                                $RejectMove = true;
                            }
                            
                        } else {
                            if ($row['wall_present'] != null || $row['player_present'] != null ) {
                                $RejectMove = true;
                            }
                        }
                    }
                    
                } 
            } else {
                // Loop on each position between the start position to the end position and verify that no wall and no pawn
                // specific case for down of the wall 
                $dbres_desc = self::DbQuery("SELECT board_X posX, board_wall wall_present, board_player player_present FROM board WHERE board_y = $toY ORDER BY board_y DESC");
                while ($row = mysql_fetch_assoc($dbres_desc) ) {
                    if ( $row['posX'] < $fromX &&  $row['posX'] >= $toX ) {
                        if( $pawnIsOnWall ){
                            if ($row['wall_present'] == null ) {
                                $pawnIsOnWall = false;
                            }
                            if ($row['player_present'] != null ) {
                                $RejectMove = true;
                            }
                            
                        } else {
                            if ($row['wall_present'] != null || $row['player_present'] != null) {
                                $RejectMove = true;
                            }
                        }
                    }
                }
            }
        }
        
        
        // throw an exception if is not in same row without pawn or wall between the disc to the final position
        if ($RejectMove) {
            ///////////////////
            // dbg
            //self::notifyAllPlayers('Error', "test", array(
            //    'invalid move' => true,
            //    'reject' => $RejectMove
            //    ));
            // end dbg
            ///////////////////
            throw new feException("Cannot move");
        }


        self::DbQuery("UPDATE board SET board_player=NULL,            board_king=NULL           WHERE board_x = $fromX AND board_y = $fromY");
        self::DbQuery("UPDATE board SET board_player='$pawnPlayerId', board_king=$pawnIsKing WHERE board_x = $toX   AND board_y = $toY");

        self::notifyAllPlayers('pawnMoved', clienttranslate('${player_name} moves a pawn'), array(
            'player_id' => $pawnPlayerId,
            'player_name' => self::getActivePlayerName(),
            'fromDiscId' => $fromDiscId,
            'toSquareId' => $toSquareId,
            'gamedatas' => $this->getAllDatas()
        ));

        // Send another notif if a pawn was eaten
        $eatenPawns = $this->findEatenPawns($toX, $toY);
        foreach ($eatenPawns as $eatenPawn) {
            list($eatenPawnX, $eatenPawnY) = $eatenPawn;
            self::DbQuery("UPDATE board SET board_player=NULL, board_king=NULL WHERE board_x = $eatenPawnX AND board_y = $eatenPawnY");
            self::notifyAllPlayers('pawnEaten', clienttranslate("Pawn at position x=$eatenPawnX,y=$eatenPawnY has been eaten by player by \${player_name} !"), array(
                'player_id' => $pawnPlayerId,
                'player_name' => self::getActivePlayerName(),
                'eatenPawnX' => $eatenPawnX,
                'eatenPawnY' => $eatenPawnY,
                'gamedatas' => $this->getAllDatas()
            ));
        }

        $this->gamestate->nextState('move');
    }

    /**
     * @param int $x : new position of the pawn moved
     * @param int $y : new position of the pawn moved
     * @return array(array(int x, int y))
     */
    private function findEatenPawns(int $x, int $y)
    {
        $activePlayer = self::getActivePlayerId();
        $positionsToTest = array(
            array('victim' => array($x, $y + 1), 'dual' => array($x, $y + 2), 'third' => array($x - 1, $y + 1), 'fourth' => array($x + 1, $y + 1)),
            array('victim' => array($x, $y - 1), 'dual' => array($x, $y - 2), 'third' => array($x - 1, $y - 1), 'fourth' => array($x - 1, $y - 1)),
            array('victim' => array($x + 1, $y), 'dual' => array($x + 2, $y), 'third' => array($x + 1, $y - 1), 'fourth' => array($x + 1, $y + 1)),
            array('victim' => array($x - 1, $y), 'dual' => array($x - 2, $y), 'third' => array($x - 1, $y - 1), 'fourth' => array($x - 1, $y + 1)),
        );

        $eatenPawns = array();
        foreach ($positionsToTest as $pos) {
            $victimPawn = $this->dbPawnAtPos($pos['victim']);
            if ($victimPawn['board_player'] == null || $victimPawn['board_player'] == $activePlayer) {
                continue;
            }
            $dualPawn = $this->dbPawnAtPos($pos['dual']);
            if ($victimPawn['board_king']) {
                $thirdPawn = $this->dbPawnAtPos($pos['third']);
                $fourthPawn = $this->dbPawnAtPos($pos['fourth']);
                if (($dualPawn['board_player'] == $activePlayer || $dualPawn['board_wall'])
                    && ($thirdPawn['board_player'] == $activePlayer || $thirdPawn['board_wall'])
                    && ($fourthPawn['board_player'] == $activePlayer || $fourthPawn['board_wall'])) {
                    array_push($eatenPawns, $pos['victim']);
                }
            } else {
                if ($dualPawn['board_player'] == $activePlayer) {
                    array_push($eatenPawns, $pos['victim']);
                }
            }
        }
        return $eatenPawns;
    }

    private function dbPawnAtPos($pos)
    {
        list($x, $y) = $pos;
        return $this->dbPawnAt($x, $y);
    }

    private function dbPawnAt($x, $y)
    {
        if ($x < 1 || $x > 9 || $y < 1 || $y > 9) {
            return array('board_player' => null, 'board_king' => null);
        }
        return self::DbQuery("SELECT board_player, board_king, board_wall FROM board WHERE board_x = $x AND board_y = $y")->fetch_assoc();
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
        $nextPlayerId = self::activeNextPlayer();

        $kingWins = self::DbQuery("SELECT board_limitWin FROM board WHERE board_king='1' ")->fetch_assoc()['board_limitWin'];
        $moscovitWin = self::DbQuery("SELECT board_x FROM board WHERE board_king='1' ")->fetch_assoc()['board_x'];
        if ($moscovitWin == null) {
            self::DbQuery("UPDATE player SET player_score='2' WHERE player_id='$activePLayer'");
            $this->gamestate->nextState('endGame');
        } elseif ($kingWins == '1') {
            self::DbQuery("UPDATE player SET player_score='1' WHERE player_id='$activePLayer'");
            $this->gamestate->nextState('endGame');
        } else {
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
