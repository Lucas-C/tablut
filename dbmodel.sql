
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):

-- CREATE TABLE IF NOT EXISTS `card` (
--   `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `card_type` varchar(16) NOT NULL,
--   `card_type_arg` int(11) NOT NULL,
--   `card_location` varchar(16) NOT NULL,
--   `card_location_arg` int(11) NOT NULL,
--   PRIMARY KEY (`card_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- Example 2: add a custom field to the standard "player" table
-- ALTER TABLE `player` ADD `player_my_custom_field` INT UNSIGNED NOT NULL DEFAULT '0';

-- Create table for define each position on the board
-- if board_king is 1 the piece is the king else is a simple piece of the game
-- if board_wall is 1 then the piece can only down of the wall but not up on the wall
-- if board_limitWin is 1 then the position is at the limit of the board (for the king it is the victory)
CREATE TABLE IF NOT EXISTS `board` (
  `board_x` smallint(5) unsigned NOT NULL,
  `board_y` smallint(5) unsigned NOT NULL,
  `board_king`  int(10) unsigned DEFAULT NULL,
  `board_wall`  int(10) unsigned DEFAULT NULL,
  `board_limitWin`  int(10) unsigned DEFAULT NULL,
  `board_player` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`board_x`,`board_y`)
) ENGINE=InnoDB;

