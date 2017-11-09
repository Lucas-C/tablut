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
 * stats.inc.php
 *
 * tablut game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.
    
    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, and "float" for floating point values.
    
    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.
*/

//   !! It is not a good idea to modify this file when a game is running !!


$stats_type = array(

    // Statistics global to table
    "table" => array(

    ),
    
    // Statistics existing for each player
    "player" => array(
    
        "discPlayedOnCorner" => array(   "id"=> 10,
                                "name" => totranslate("Discs played on a corner"), 
                                "type" => "int" ),
                                
        "discPlayedOnBorder" => array(   "id"=> 11,
                                "name" => totranslate("Discs played on a border"), 
                                "type" => "int" ),

        "discPlayedOnCenter" => array(   "id"=> 12,
                                "name" => totranslate("Discs played on board center part"), 
                                "type" => "int" ),

        "turnedOver" => array(   "id"=> 13,
                                "name" => totranslate("Number of discs turned over"), 
                                "type" => "int" )    
    )

);


