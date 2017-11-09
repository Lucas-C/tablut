<?php
/**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * tablut implementation : © Gregory Isabelli <gisabelli@boardgamearena.com>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * tablut.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */  
  

require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class tablut extends Table
{
	function __construct( )
	{
        	
        parent::__construct();self::initGameStateLabels( array()  );
	}
	
    protected function getGameName( )
    {
        return "tablut";
    }	

    protected function setupNewGame( $players, $options = array() )
    {    
        // Create players
        $default_color = array( "000000", "ffffff" );
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_color );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
            
            if( $color == '000000' )
                $blackplayer_id = $player_id;
            else
                $whiteplayer_id = $player_id;
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reloadPlayersBasicInfos();
        
        // Init the board
        $sql = "INSERT INTO board (board_x,board_y,board_player) VALUES ";
        $sql_values = array();
        for( $x=1; $x<=8; $x++ )
        {
            for( $y=1; $y<=8; $y++ )
            {
                $disc_value = "NULL";
                if( ($x==4 && $y==4) || ($x==5 && $y==5) )  // Initial positions of white player
                    $disc_value = "'$whiteplayer_id'";
                else if( ($x==4 && $y==5) || ($x==5 && $y==4) )  // Initial positions of black player
                    $disc_value = "'$blackplayer_id'";
                    
                $sql_values[] = "('$x','$y',$disc_value)";
            }
        }
        $sql .= implode( $sql_values, ',' );
        self::DbQuery( $sql );
        
        // Init stats
        self::initStat( 'player', 'discPlayedOnCorner', 0 );
        self::initStat( 'player', 'discPlayedOnBorder', 0 );
        self::initStat( 'player', 'discPlayedOnCenter', 0 );
        self::initStat( 'player', 'turnedOver', 0 );
        
        // Active first player
        self::activeNextPlayer();
    }

    // Get all datas (complete reset request from client side)
    protected function getAllDatas()
    {
        $result = array( 'players' => array() );
    
        // Add players specific infos
        $sql = "SELECT player_id id, player_score score ";
        $sql .= "FROM player ";
        $sql .= "WHERE 1 ";
        $dbres = self::DbQuery( $sql );
        while( $player = mysql_fetch_assoc( $dbres ) )
        {
            $result['players'][ $player['id'] ] = $player;
        }
        
        // Get tablut board disc
        $result['board'] = self::getObjectListFromDB( "SELECT board_x x, board_y y, board_player player
                                                       FROM board
                                                       WHERE board_player IS NOT NULL" );
  
        return $result;
    }
    


    function getGameProgression()
    {
        // Game progression: get the number of free squares
        // (number of free squares goes from 60 to 0
        $freeSquare = self::getUniqueValueFromDb( "SELECT COUNT( board_x ) FROM board WHERE board_player IS NULL" );
        
        return round( ( 60-$freeSquare )/60*100 );
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions    (functions used everywhere)
////////////    

    // Get the list of returned disc when "player" we play at this place ("x", "y"),
    //  or a void array if no disc is returned (invalid move)
    function getTurnedOverDiscs( $x, $y, $player, $board )
    {
        $turnedOverDiscs = array();
        
        if( $board[ $x ][ $y ] === null ) // If there is already a disc on this place, this can't be a valid move
        {
            // For each directions...
            $directions = array(
                array( -1,-1 ), array( -1,0 ), array( -1, 1 ), array( 0, -1),
                array( 0,1 ), array( 1,-1), array( 1,0 ), array( 1, 1 )
            );
            
            foreach( $directions as $direction )
            {
                // Starting from the square we want to place a disc...
                $current_x = $x;
                $current_y = $y;
                $bContinue = true;
                $mayBeTurnedOver = array();

                while( $bContinue )
                {
                    // Go to the next square in this direction
                    $current_x += $direction[0];
                    $current_y += $direction[1];
                    
                    if( $current_x<1 || $current_x>8 || $current_y<1 || $current_y>8 )
                        $bContinue = false; // Out of the board => stop here for this direction
                    else if( $board[ $current_x ][ $current_y ] === null )
                        $bContinue = false; // An empty square => stop here for this direction
                    else if( $board[ $current_x ][ $current_y ] != $player )
                    {
                        // There is a disc from our opponent on this square
                        // => add it to the list of the "may be turned over", and continue on this direction
                        $mayBeTurnedOver[] = array( 'x' => $current_x, 'y' => $current_y );
                    }
                    else if( $board[ $current_x ][ $current_y ] == $player )
                    {
                        // This is one of our disc
                        
                        if( count( $mayBeTurnedOver ) == 0 )
                        {
                            // There is no disc to be turned over between our 2 discs => stop here for this direction
                            $bContinue = false;
                        }
                        else
                        {
                            // We found some disc to be turned over between our 2 discs
                            // => add them to the result and stop here for this direction
                            $turnedOverDiscs = array_merge( $turnedOverDiscs, $mayBeTurnedOver );
                            $bContinue = false;
                        }
                    }
                }
            }
        }
        
        return $turnedOverDiscs;
    }
    
    // Get the complete board with a double associative array
    function getBoard()
    {
        return self::getDoubleKeyCollectionFromDB( "SELECT board_x x, board_y y, board_player player
                                                       FROM board", true );
    }

    // Get the list of possible moves (x => y => true)
    function getPossibleMoves( $player_id )
    {
        $result = array();
        
        $board = self::getBoard();
        
        for( $x=1; $x<=8; $x++ )
        {
            for( $y=1; $y<=8; $y++ )
            {
                $returned = self::getTurnedOverDiscs( $x, $y, $player_id, $board );
                if( count( $returned ) == 0 )
                {
                    // No discs returned => not a possible move
                }
                else
                {
                    // Okay => set this coordinate to "true"
                    if( ! isset( $result[$x] ) )
                        $result[$x] = array();
                        
                    $result[$x][$y] = true;
                }
            }
        }
                
        return $result;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    function playDisc( $x, $y )
    {
        // Check that this player is active and that this action is possible at this moment
        self::checkAction( 'playDisc' );  
        
        $player_id = self::getActivePlayerId(); 
        
        // Now, check if this is a possible move
        $board = self::getBoard();
        $turnedOverDiscs = self::getTurnedOverDiscs( $x, $y, $player_id, $board );
        
        if( count( $turnedOverDiscs ) > 0 )
        {
            // This move is possible!
            
            // Let's place a disc at x,y and return all "$returned" discs to the active player
            
            $sql = "UPDATE board SET board_player='$player_id'
                    WHERE ( board_x, board_y) IN ( ";
            
            foreach( $turnedOverDiscs as $turnedOver )
            {
                $sql .= "('".$turnedOver['x']."','".$turnedOver['y']."'),";
            }
            $sql .= "('$x','$y') ) ";
                       
            self::DbQuery( $sql );
            
            // Update scores according to the number of disc on board
            $sql = "UPDATE player
                    SET player_score = (
                    SELECT COUNT( board_x ) FROM board WHERE board_player=player_id
                    )";
            self::DbQuery( $sql );
            
            // Statistics
            self::incStat( count( $turnedOverDiscs ), "turnedOver", $player_id );
            if( ($x==1 && $y==1) || ($x==8 && $y==1) || ($x==1 && $y==8) || ($x==8 && $y==8) )
                self::incStat( 1, 'discPlayedOnCorner', $player_id );
            else if( $x==1 || $x==8 || $y==1 || $y==8 )
                self::incStat( 1, 'discPlayedOnBorder', $player_id );
            else if( $x>=3 && $x<=6 && $y>=3 && $y<=6 )
                self::incStat( 1, 'discPlayedOnCenter', $player_id );
            
            // Notify
            self::notifyAllPlayers( "playDisc", clienttranslate( '${player_name} plays a disc and turns over ${returned_nbr} disc(s)' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'returned_nbr' => count( $turnedOverDiscs ),
                'x' => $x,
                'y' => $y
            ) );

            self::notifyAllPlayers( "turnOverDiscs", '', array(
                'player_id' => $player_id,
                'turnedOver' => $turnedOverDiscs
            ) );
            
            $newScores = self::getCollectionFromDb( "SELECT player_id, player_score FROM player", true );
            self::notifyAllPlayers( "newScores", "", array(
                "scores" => $newScores
            ) );
            
            // Then, go to the next state
            $this->gamestate->nextState( 'playDisc' );
        }
        else
            throw new feException( "Impossible move" );
    }
    
    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    function argPlayerTurn()
    {
        return array(
            'possibleMoves' => self::getPossibleMoves( self::getActivePlayerId() )
        );
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state reactions   (reactions to game planned states from state machine
////////////

    function stNextPlayer()
    {
        // Active next player
        $player_id = self::activeNextPlayer();

        // Check if both player has at least 1 discs, and if there are free squares to play
        $player_to_discs = self::getCollectionFromDb( "SELECT board_player, COUNT( board_x )
                                                       FROM board
                                                       GROUP BY board_player", true );

        if( ! isset( $player_to_discs[ null ] ) )
        {
            // Index 0 has not been set => there's no more free place on the board !
            // => end of the game
            $this->gamestate->nextState( 'endGame' );
            return ;
        }
        else if( ! isset( $player_to_discs[ $player_id ] ) )
        {
            // Active player has no more disc on the board => he looses immediately
            $this->gamestate->nextState( 'endGame' );
            return ;
        }
        
        // Can this player play?

        $possibleMoves = self::getPossibleMoves( $player_id );
        if( count( $possibleMoves ) == 0 )
        {

            // This player can't play
            // Can his opponent play ?
            $opponent_id = self::getUniqueValueFromDb( "SELECT player_id FROM player WHERE player_id!='$player_id' " );
            if( count( self::getPossibleMoves( $opponent_id ) ) == 0 )
            {
                // Nobody can move => end of the game
                $this->gamestate->nextState( 'endGame' );
            }
            else
            {            
                // => pass his turn
                $this->gamestate->nextState( 'cantPlay' );
            }
        }
        else
        {
            // This player can play. Give him some extra time
            self::giveExtraTime( $player_id );
            $this->gamestate->nextState( 'nextTurn' );
        }
    }



//////////////////////////////////////////////////////////////////////////////
//////////// End of game management
////////////    
 
  

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    function zombieTurn( $state, $active_player )
    {
        if( $state['name'] == 'playerTurn' )
        {
            $this->gamestate->nextState( "zombiePass" );
        }
        else
            throw new feException( "Zombie mode not supported at this game state:".$state['name'] );
    }
   
   
}
  

