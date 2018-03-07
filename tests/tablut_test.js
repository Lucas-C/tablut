const Tablut = require('../tablut').tablut;

var assert = require('assert');


describe('listAvailableMoves', () => {
    it('should return 4 available moves for a single pawn in the middle of a 3x3 board', () => {
        const board = [
            {x: '1', y: '1', player: null, wall: null},
            {x: '1', y: '2', player: null, wall: null},
            {x: '1', y: '3', player: null, wall: null},
            {x: '2', y: '1', player: null, wall: null},
            {x: '2', y: '2', player: 'P1', wall: null},
            {x: '2', y: '3', player: null, wall: null},
            {x: '3', y: '1', player: null, wall: null},
            {x: '3', y: '2', player: null, wall: null},
            {x: '3', y: '3', player: null, wall: null},
        ];

        const tablut = new Tablut();
        const moves = [...tablut.listAvailableMoves({
            board: board,
            pawnPos: {x: '2', y: '2'},
        })];

        assert.equal(moves.length, 4);
    })
})

