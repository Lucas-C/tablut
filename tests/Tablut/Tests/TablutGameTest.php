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
}
