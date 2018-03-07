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
        const selectedDiscId = 'disc_2_2';

        const tablut = new Tablut(3);

        const moves = [...tablut.listAvailableMoves(board, selectedDiscId)];
        assert.equal(moves.length, 4);
    })
})

