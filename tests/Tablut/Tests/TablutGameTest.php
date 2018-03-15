<?php

namespace Tablut\Tests;

use PHPUnit\Framework\TestCase;

use BGAWorkbench\Test\TableInstanceBuilder;
use BGAWorkbench\Test\TestHelp;

class TablutGameTest extends TestCase
{
    use TestHelp;

    protected function createGameTableInstanceBuilder() : TableInstanceBuilder
    {
        return $this->gameTableInstanceBuilder()
            ->setPlayersWithIds([66, 77])
            ->overridePlayersPostSetup([
                66 => ['player_no' => 1],
                77 => ['player_no' => 2]
            ]);
    }

    public function testSimpleGameSetupAndInitialPawnsCount()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer();
        assertThat($this->table->fetchDbRows('board', ['board_player' => 66]), arrayWithSize(16));
        assertThat($this->table->fetchDbRows('board', ['board_player' => 77]), arrayWithSize(9));
    }

    public function testFindEatenPawnsReturnsNone()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer()
            ->stubActivePlayerId(66);
        assertThat($game->findEatenPawns(5, 5), arrayWithSize(0));
    }

    public function testInitialGetGameProgression()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer()
            ->stubActivePlayerId(66);
        $this->assertEquals(0, $game->getGameProgression());
    }

    public function testSimpleGetGameProgression()
    {
        $game = $this->table
            ->setupNewGame()
            ->createGameInstanceWithNoBoundedPlayer()
            ->stubActivePlayerId(66);

        // Testing game progression after the king has moved and 1 muscovite was captured
        $game->DbQuery("UPDATE board SET board_king=NULL, board_player=NULL WHERE board_x='5' AND board_y='5'");
        $game->DbQuery("UPDATE board SET board_king='1',  board_player='66' WHERE board_x='6' AND board_y='6'");

        $game->DbQuery("UPDATE board SET board_player=NULL WHERE board_x='1' AND board_y='5'");

        $this->assertEquals(24, $game->getGameProgression());
    }
}
