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

define('BLACK_PLAYER_COLOR', '000000');
define('WHITE_PLAYER_COLOR', 'ffffff');

class Tablut extends Table
{
    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([
            'King wins on the edges variant' => 100,
        ]);
    }

    protected function isRuleVariant()
    {
        return $this->gamestate->table_globals && $this->gamestate->table_globals[100] == '0';
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
        $activePlayerIsMuscovite = $this->setupPlayers($players);
        $this->setupBoard($players);
        $this->setupStats($activePlayerIsMuscovite);
        $this->activeNextPlayer();
        if ($activePlayerIsMuscovite) {
            self::notifyPlayer(self::getActivePlayerId(), 'playerIsBlack', clienttranslate('You play the black pawns, the Muscovites'), []);
        } else {
            self::notifyPlayer(self::getActivePlayerId(), 'playerIsWhite', clienttranslate('You play the white pawns, the Swedes'), []);
        }
    }

    /**
     * @param array $players
     */
    private function setupPlayers(array $players)
    {
        $default_color = [BLACK_PLAYER_COLOR, WHITE_PLAYER_COLOR]; // black <=> Muscovites / white <=> Swedes
        $sql = 'INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES';
        $values = [];
        $activePlayerIsMuscovite = null;
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_color);
            if (self::getActivePlayerId() == $player_id) {
                $activePlayerIsMuscovite = ($color == BLACK_PLAYER_COLOR);
            }
            $playerName = addslashes($player['player_name']);
            $playerAvatar = addslashes($player['player_avatar']);
            $values[] = "('$player_id','$color','$player[player_canal]','$playerName','$playerAvatar')";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
        self::reloadPlayersBasicInfos();
        return $activePlayerIsMuscovite;
    }

    private function setupBoard(array $players)
    {
        /* Initialize all the board */
        $sql_values = [];
        $sql = 'INSERT INTO board (board_x, board_y, board_wall) VALUES ';
        for ($x=1; $x<=9; $x++) {
            for ($y=1; $y<=9; $y++) {
                $sql .= "('$x', '$y', NULL) ";
                if ($x!=9 or $y!=9) {
                    $sql .= ",";
                }
            }
        }
        self::DbQuery($sql);

        $player0 = array_keys($players)[0];
        $player1 = array_keys($players)[1];

        /* Initialize the player 1 pieces */
        self::DbQuery("UPDATE board SET board_player='$player1', board_wall='2', board_king='1' WHERE ( board_x, board_y) IN (('5','5'))");
        self::DbQuery("UPDATE board SET board_player='$player1' WHERE ( board_x, board_y) IN (('3','5'), ('4','5'), ('6','5'), ('7','5'))");
        self::DbQuery("UPDATE board SET board_player='$player1' WHERE ( board_x, board_y) IN (('5','3'), ('5','4'), ('5','6'), ('5','7'))");
        
        if ($this->isRuleVariant()) {
            /* Initialize the player 0 pieces */
            self::DbQuery("UPDATE board SET board_player='$player0', board_wall='1' WHERE ( board_x, board_y) IN (('4','1'), ('5','1'), ('6','1'), ('5','2') )");
            self::DbQuery("UPDATE board SET board_player='$player0', board_wall='1' WHERE ( board_x, board_y) IN (('4','9'), ('5','9'), ('6','9'), ('5','8') )");
            self::DbQuery("UPDATE board SET board_player='$player0', board_wall='1' WHERE ( board_x, board_y) IN (('1','4'), ('1','5'), ('1','6'), ('2','5') )");
            self::DbQuery("UPDATE board SET board_player='$player0', board_wall='1' WHERE ( board_x, board_y) IN (('9','4'), ('9','5'), ('9','6'), ('8','5') )");

            /* Initialize the limit winning game */
            self::DbQuery("UPDATE board SET board_limitWin='1' WHERE ( board_x, board_y) IN (('1','1'), ('1','2'), ('1','3'), ('1','7'), ('1','8'), ('1','9'))");
            self::DbQuery("UPDATE board SET board_limitWin='1' WHERE ( board_x, board_y) IN (('9','1'), ('9','2'), ('9','3'), ('9','7'), ('9','8'), ('9','9'))");
            self::DbQuery("UPDATE board SET board_limitWin='1' WHERE ( board_x, board_y) IN (('2','1'), ('3','1'), ('7','1'), ('8','1'))");
            self::DbQuery("UPDATE board SET board_limitWin='1' WHERE ( board_x, board_y) IN (('2','9'), ('3','9'), ('7','9'), ('8','9'))");
        } else {
            /* Initialize the player 0 pieces */
            self::DbQuery("UPDATE board SET board_player='$player0' WHERE ( board_x, board_y) IN (('4','1'), ('5','1'), ('6','1'), ('5','2') )");
            self::DbQuery("UPDATE board SET board_player='$player0' WHERE ( board_x, board_y) IN (('4','9'), ('5','9'), ('6','9'), ('5','8') )");
            self::DbQuery("UPDATE board SET board_player='$player0' WHERE ( board_x, board_y) IN (('1','4'), ('1','5'), ('1','6'), ('2','5') )");
            self::DbQuery("UPDATE board SET board_player='$player0' WHERE ( board_x, board_y) IN (('9','4'), ('9','5'), ('9','6'), ('8','5') )");

            /* Initialize the limit winning game */
            self::DbQuery("UPDATE board SET board_limitWin='1', board_wall='1' WHERE ( board_x, board_y) IN (('1','1'), ('1','9'))");
            self::DbQuery("UPDATE board SET board_limitWin='1', board_wall='1' WHERE ( board_x, board_y) IN (('9','1'), ('9','9'))");
        }
    }

    private function setupStats($activePlayerIsMuscovite)
    {
        // Table stats:
        $this->initStat('table', 'turns_number', 0);
        $this->initStat('table', 'muscovites_captured', 0);
        $this->initStat('table', 'swedes_captured', 0);
        // Player stats:
        if ($activePlayerIsMuscovite) {
            $this->initStat('player', 'games_playing_muscovites', 1);
            $this->initStat('player', 'games_playing_swedes', 0);
        } else {
            $this->initStat('player', 'games_playing_muscovites', 0);
            $this->initStat('player', 'games_playing_swedes', 1);
        }
        $this->initStat('player', 'muscovites_captured', 0);
        $this->initStat('player', 'swedes_captured', 0);
    }


    /**
     * Gather all informations about current game situation (visible by the current player).
     * The method is called each time the game interface is displayed to a player, ie:
     *  - when the game starts
     *  - when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = ['players' => []];
        $dbRequest = self::DbQuery('SELECT player_id id FROM player');
        while ($player = $dbRequest->fetch_assoc()) {
            $result['players'][ $player['id'] ] = $player;
        }

        $result['board'] = self::getObjectListFromDB('SELECT
                                                          board_x x,
                                                          board_y y,
                                                          board_player player,
                                                          board_king king,
                                                          board_wall wall,
                                                          board_limitWin limitWin
                                                      FROM board');

        $result['turns_number'] = $this->getStat('turns_number');
        $result['game_options'] = $this->gamestate->table_globals; // To provide access to variants in JS

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
        $kingPos = self::DbQuery("SELECT board_x x, board_y y FROM board WHERE board_king = '1'")->fetch_assoc();

        // The number of free squares goes from 56 (at the beginning of the game, 81-16-9) to 77 (hypothetically, only the king and 3 Swedes remain
        $freeSquaresCount = self::getUniqueValueFromDb('SELECT COUNT( 1 ) FROM board WHERE board_player IS NULL');

        // Game progression = $freeSquaresCount scaled between 0 and 80 + 20 if king left the throne
        return round(($freeSquaresCount - 56)*80/21) + ($kingPos['x'] != '5' || $kingPos['y'] != '5' ? 20 : 0);
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    public function countWinningMoves()
    {
        $kingPos = self::DbQuery("SELECT board_x x, board_y y, board_limitWin winning_pos FROM board WHERE board_king != 'NULL'")->fetch_assoc();
        if ($kingPos['winning_pos'] == '1') {
            return 0;
        }
        $winningMovesCount = 0;
        $axisToTest = [
            ['queryCondition' => "board_x > ${kingPos['x']} and board_y = ${kingPos['y']}", 'isOnEdge' => ($kingPos['y'] == '1' || $kingPos['y'] == '9')],
            ['queryCondition' => "board_x < ${kingPos['x']} and board_y = ${kingPos['y']}", 'isOnEdge' => ($kingPos['y'] == '1' || $kingPos['y'] == '9')],
            ['queryCondition' => "board_x = ${kingPos['x']} and board_y > ${kingPos['y']}", 'isOnEdge' => ($kingPos['x'] == '1' || $kingPos['x'] == '9')],
            ['queryCondition' => "board_x = ${kingPos['x']} and board_y < ${kingPos['y']}", 'isOnEdge' => ($kingPos['x'] == '1' || $kingPos['x'] == '9')],
        ];
        $extraQueryCondition = $this->isRuleVariant() ? "or board_wall != 'null'" : '';
        foreach ($axisToTest as $axis) {
            $searchResult = self::DbQuery("SELECT board_x x, board_y y FROM board WHERE ${axis['queryCondition']} and (board_player != 'null' $extraQueryCondition)")->fetch_assoc();
            if ($this->isRuleVariant()) {
                if ($searchResult == null) {
                    $winningMovesCount += 1;
                }
            } else {
                if ($searchResult == null and $axis['isOnEdge']) {
                    $winningMovesCount += 1;
                }
            }
        }
        return $winningMovesCount;
    }

    public function logRaichiOrTuichi($winningMovesCount)
    {
        $goalName = ($this->isRuleVariant() ? 'an edge' : 'a corner');
        if ($winningMovesCount == 1) {
            self::notifyAllPlayers('Raichi', clienttranslate("Raichi! (the King has a clear paths to $goalName)"), []);
        } elseif ($winningMovesCount > 1) {
            self::notifyAllPlayers('Tuichi', clienttranslate("Tuichi! (the King gained two clear paths to $goalName: the game ends)"), []);
        }
    }

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
        if (self::getActivePlayerId() != $pawnPlayerId) {
            throw new feException("This pawn belongs to your opponent: pawnPlayerId=$pawnPlayerId | pawnIsKing=$pawnIsKing");
        }
        $pawnIsOnWall = $srcPawnFromDb['board_wall'] ? true : false ;
        
        // reject if a pawn is present
        $dstSquareFromDb = self::DbQuery("SELECT board_player FROM board WHERE board_x = $toX AND board_y = $toY")->fetch_assoc();
        if ($dstSquareFromDb['board_player'] != null) {
            throw new feException("Cannot move onto another pawn");
        }
        
        // reject diagonal move
        if ($toX != $fromX && $toY != $fromY) {
            throw new feException("Cannot move on diagonal");
        }
        
        // throw an exception if is not in same column without pawn or wall between the disc to the final position
        if ($toX == $fromX) {
            if ($fromY < $toY) {
                $dbres_asc = self::DbQuery("SELECT board_y posY, board_wall wall_type, board_player player_present, board_limitWin winning_pos FROM board WHERE board_x = $toX ORDER BY board_y ASC");
                // Loop on each position between the start position to the end position and verify that no wall and no pawn
                // specific case for down of the wall
                while ($column = $dbres_asc->fetch_assoc()) {
                    if ($fromY < $column['posY'] && $column['posY'] <= $toY) {
                        if ($pawnIsOnWall && $column['wall_type'] != null) {
                            continue;
                        }
                        $pawnIsOnWall = false;
                        $this->ensureNoWall($toX, $column['posY'], $column['player_present'] != null, $pawnIsKing != 'NULL', $column['wall_type'], $column['winning_pos'] == '1', $column['posY'] == $toY);
                    }
                }
            } else {
                $dbres_desc = self::DbQuery("SELECT board_y posY, board_wall wall_type, board_player player_present, board_limitWin winning_pos FROM board WHERE board_x = $toX ORDER BY board_y DESC");
                // Loop on each position between the start position to the end position and verify that no wall and no pawn
                // specific case for down of the wall
                while ($column = $dbres_desc->fetch_assoc()) {
                    if ($column['posY'] < $fromY &&  $column['posY'] >= $toY) {
                        if ($pawnIsOnWall && $column['wall_type'] != null) {
                            continue;
                        }
                        $pawnIsOnWall = false;
                        $this->ensureNoWall($toX, $column['posY'], $column['player_present'] != null, $pawnIsKing != 'NULL', $column['wall_type'], $column['winning_pos'] == '1', $column['posY'] == $toY);
                    }
                }
            }
        } else { // $toY == $fromY
            if ($fromX < $toX) {
                // Loop on each position between the start position to the end position and verify that no wall and no pawn
                // specific case for down of the wall
                $dbres_asc = self::DbQuery("SELECT board_X posX, board_wall wall_type, board_player player_present, board_limitWin winning_pos FROM board WHERE board_y = $toY ORDER BY board_x ASC");
                while ($row = $dbres_asc->fetch_assoc()) {
                    if ($fromX < $row['posX'] && $row['posX'] <= $toX) {
                        if ($pawnIsOnWall && $row['wall_type'] != null) {
                            continue;
                        }
                        $pawnIsOnWall = false;
                        $this->ensureNoWall($row['posX'], $toY, $row['player_present'] != null, $pawnIsKing != 'NULL', $row['wall_type'], $row['winning_pos'] == '1', $row['posX'] == $toX);
                    }
                }
            } else {
                // Loop on each position between the start position to the end position and verify that no wall and no pawn
                // specific case for down of the wall
                $dbres_desc = self::DbQuery("SELECT board_X posX, board_wall wall_type, board_player player_present, board_limitWin winning_pos FROM board WHERE board_y = $toY ORDER BY board_x DESC");
                while ($row = $dbres_desc->fetch_assoc()) {
                    if ($row['posX'] < $fromX && $row['posX'] >= $toX) {
                        if ($pawnIsOnWall && $row['wall_type'] != null) {
                            continue;
                        }
                        $pawnIsOnWall = false;
                        $this->ensureNoWall($row['posX'], $toY, $row['player_present'] != null, $pawnIsKing != 'NULL', $row['wall_type'], $row['winning_pos'] == '1', $row['posX'] == $toX);
                    }
                }
            }
        }

        self::DbQuery("UPDATE board SET board_player=NULL,            board_king=NULL           WHERE board_x = $fromX AND board_y = $fromY");
        self::DbQuery("UPDATE board SET board_player='$pawnPlayerId', board_king=$pawnIsKing WHERE board_x = $toX   AND board_y = $toY");

        $fromBoardPos = $this->posXYtoBoardPos($fromX, $fromY);
        $toBoardPos = $this->posXYtoBoardPos($toX, $toY);
        self::notifyAllPlayers('pawnMoved', clienttranslate('${player_name} moves a pawn from ${fromBoardPos} to ${toBoardPos}'), [
            'player_id' => $pawnPlayerId,
            'player_name' => self::getActivePlayerName(),
            'fromDiscId' => $fromDiscId,
            'toSquareId' => $toSquareId,
            'fromBoardPos' => $fromBoardPos,
            'toBoardPos' => $toBoardPos,
            'gamedatas' => $this->getAllDatas()
        ]);
        
        // Check for eaten pawns (but not for the king in the base rule)
        if ($this->isRuleVariant() || $pawnIsKing == 'NULL') {
            // Send another notif if pawns were eaten
            $eatenPawns = $this->findEatenPawns($toX, $toY);
            foreach ($eatenPawns as $eatenPawn) {
                list($eatenPawnX, $eatenPawnY) = $eatenPawn;
                $eatenPawnBoardPos = $this->posXYtoBoardPos($eatenPawnX, $eatenPawnY);
                $kingInfo = self::DbQuery("SELECT board_king FROM board WHERE board_x = $eatenPawnX AND board_y = $eatenPawnY")->fetch_assoc();
                if ($kingInfo['board_king'] == '1') {
                    self::DbQuery("UPDATE board SET board_king=2 WHERE board_x = $eatenPawnX AND board_y = $eatenPawnY");
                } else {
                    self::DbQuery("UPDATE board SET board_player=NULL, board_king=NULL WHERE board_x = $eatenPawnX AND board_y = $eatenPawnY");
                    self::notifyAllPlayers('pawnEaten', clienttranslate('Pawn at position ${eatenPawnBoardPos} has been eaten by player by ${player_name} !'), [
                        'player_id' => $pawnPlayerId,
                        'player_name' => self::getActivePlayerName(),
                        'eatenPawnX' => $eatenPawnX,
                        'eatenPawnY' => $eatenPawnY,
                        'eatenPawnBoardPos' => $eatenPawnBoardPos,
                        'gamedatas' => $this->getAllDatas()
                    ]);
                }
                if ($this->dbPawnColor($eatenPawnX, $eatenPawnY) == BLACK_PLAYER_COLOR) {
                    $this->incStat(1, 'muscovites_captured'); // TABLE stat update
                    $this->incStat(1, 'muscovites_captured', $pawnPlayerId); // PLAYER stat update
                } else {
                    $this->incStat(1, 'swedes_captured'); // TABLE stat update
                    $this->incStat(1, 'swedes_captured', $pawnPlayerId); // PLAYER stat update
                }
            }
        }

        $winningMovesCount = $this->countWinningMoves();
        $this->logRaichiOrTuichi($winningMovesCount);

        $this->incStat(1, 'turns_number'); // TABLE stat update

        $this->gamestate->nextState('move');
    }

    protected function ensureNoWall(int $x, int $y, bool $pawnPresent, bool $pawnIsKing, $wallType, bool $isKingWinningPos, bool $isMoveFinalDest)
    {
        if ($pawnPresent) {
            throw new feException("Cannot move on ($x, $y) : a pawn is already present");
        }
        if ($wallType == '2') {
            // The throne can always been passed by the king,
            // but not by the pawns in the variant
            if (!$pawnIsKing && ($this->isRuleVariant() || $isMoveFinalDest)) {
                throw new feException("Cannot move on ($x, $y) : a fortress is blocking");
            }
        } elseif ($wallType == '1') {
            // Only case this is allowed is when the king goes on the corner fortresses
            if (!($pawnIsKing && $isKingWinningPos)) {
                throw new feException("Cannot move on ($x, $y) : a fortress is blocking");
            }
        }
    }

    /**
     * @param int $x : new position of the pawn moved
     * @param int $y : new position of the pawn moved
     * @return array(array(int x, int y))
     */
    public function findEatenPawns(int $x, int $y)
    {
        $activePlayer = self::getActivePlayerId();
        $positionsToTest = [
            ['victim' => [$x, $y + 1], 'dual' => [$x, $y + 2], 'third' => [$x - 1, $y + 1], 'fourth' => [$x + 1, $y + 1]],
            ['victim' => [$x, $y - 1], 'dual' => [$x, $y - 2], 'third' => [$x - 1, $y - 1], 'fourth' => [$x + 1, $y - 1]],
            ['victim' => [$x + 1, $y], 'dual' => [$x + 2, $y], 'third' => [$x + 1, $y - 1], 'fourth' => [$x + 1, $y + 1]],
            ['victim' => [$x - 1, $y], 'dual' => [$x - 2, $y], 'third' => [$x - 1, $y - 1], 'fourth' => [$x - 1, $y + 1]],
        ];

        $eatenPawns = [];
        // if the active pawn is the king, ignore the capture
        if (($this->dbPawnAt($x, $y))['board_king'] != null) {
            return $eatenPawns;
        }
        foreach ($positionsToTest as $pos) {
            $victimPawn = $this->dbPawnAtPos($pos['victim']);
            // if the victim is a board or the active player go to the next direction
            if ($victimPawn['board_player'] == null || $victimPawn['board_player'] == $activePlayer) {
                continue;
            }
            $dualPawn = $this->dbPawnAtPos($pos['dual']);
            if ($victimPawn['board_king']) {
                $thirdPawn = $this->dbPawnAtPos($pos['third']);
                $fourthPawn = $this->dbPawnAtPos($pos['fourth']);
                if (($dualPawn['board_player'] == $activePlayer || $dualPawn['board_wall'] || $this->isPosOutOfBoard($pos['dual']))
                    && ($thirdPawn['board_player'] == $activePlayer || $thirdPawn['board_wall'] || $this->isPosOutOfBoard($pos['third']))
                    && ($fourthPawn['board_player'] == $activePlayer || $fourthPawn['board_wall'] || $this->isPosOutOfBoard($pos['fourth']))) {
                    array_push($eatenPawns, $pos['victim']);
                }
            } else {
                // capture the victime if the dual board player is the active player and if not the king
                if ($dualPawn['board_king'] == null and ($dualPawn['board_player'] == $activePlayer || (!$this->isRuleVariant() && $this->isCorner($pos['dual'])))) {
                    array_push($eatenPawns, $pos['victim']);
                }
            }
        }
        return $eatenPawns;
    }

    private function isCorner($pos)
    {
        list($x, $y) = $pos;
        return ($x == 1 && $y == 1)
            || ($x == 1 && $y == 9)
            || ($x == 9 && $y == 1)
            || ($x == 9 && $y == 9);
    }

    private function dbPawnAtPos($pos)
    {
        list($x, $y) = $pos;
        return $this->dbPawnAt($x, $y);
    }

    private function dbPawnAt($x, $y)
    {
        if ($this->isPosOutOfBoard([$x, $y])) {
            return ['board_player' => null, 'board_king' => null, 'board_wall' => null];
        }
        return self::DbQuery("SELECT board_player, board_king, board_wall FROM board WHERE board_x = $x AND board_y = $y")->fetch_assoc();
    }

    private function isPosOutOfBoard($pos)
    {
        list($x, $y) = $pos;
        return $x < 1 || $x > 9 || $y < 1 || $y > 9;
    }

    private function dbPawnColor($x, $y)
    {
        return self::DbQuery("SELECT player_color FROM board, player WHERE board_x = $x AND board_y = $y AND player_id = board_player")->fetch_assoc()['player_color'];
    }

    private function posXYtoBoardPos($x, $y)
    {
        return chr(64 + $x) . $y;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    public function argPlayerTurn()
    {
        return [];
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state reactions   (reactions to game planned states from state machine
////////////

    public function stNextPlayer()
    {
        $activePLayer = self::getActivePlayerId();
        $nextPlayerId = self::activeNextPlayer();

        $kingWins = self::DbQuery("SELECT board_limitWin FROM board WHERE board_king='1' ")->fetch_assoc()['board_limitWin'] == '1';
        $kingWins |= $this->countWinningMoves() > 1;
        $moscovitWin = self::DbQuery("SELECT board_x FROM board WHERE board_king='1' ")->fetch_assoc()['board_x'] == null;
        if ($moscovitWin) {
            self::DbQuery("UPDATE player SET player_score='2' WHERE player_id='$activePLayer'");
            $this->setStat(0, 'swedes_won');
            $this->gamestate->nextState('endGame');
        } elseif ($kingWins) {
            self::DbQuery("UPDATE player SET player_score='1' WHERE player_id='$activePLayer'");
            $this->setStat(1, 'swedes_won');
            $this->gamestate->nextState('endGame');
        } else {
            self::giveExtraTime($nextPlayerId);
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
