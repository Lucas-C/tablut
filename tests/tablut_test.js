const assert = require('assert')

const Tablut = require('../tablut')


function boardBuilder({size = 9} = {}) {
    const matrix = [...Array(size)].map((_, i) =>
        [...Array(size)].map((_, j) =>
            ({x: '' + (i + 1), y: '' + (j + 1), player: null, wall: null}),
        )
    )
    return {
        board: Array.prototype.concat(...matrix),  // flatten
        at: (x, y) => matrix[x - 1][y - 1],
    }
}


describe('listAvailableMoves', () => {
    const tablut = new Tablut()
    const board = (() => {
        const builder = boardBuilder({size: 3})
        builder.at(2, 2).player = 'P1'
        return builder.board
    })()
    tablut.gamedatas = {board: board}

    it('should return 4 available moves for a single pawn in the middle of a 3x3 board', () => {
        assert.equal([...tablut.listAvailableMoves({x: 2, y: 2})].length, 4)
    })
})

describe('relativeDirection', () => {
    const relativeDirection = Tablut.prototype.relativeDirection

    it('should throw an error when going from the top left of the board to the bottom right', () => {
        assert.throws(() => relativeDirection({x: 1, y: 1}, {x: 9, y: 9}))
    })

    it('should throw an error when source position equals destination', () => {
        assert.throws(() => relativeDirection({x: 5, y: 5}, {x: 5, y: 5}))
    })

    it('should return LEFT for a destination position on the left side of source', () => {
        assert.equal(relativeDirection({x: 2, y: 1}, {x: 1, y: 1}), 'LEFT')
    })

    it('should return RIGHT for a destination position on the right side of source', () => {
        assert.equal(relativeDirection({x: 5, y: 5}, {x: 6, y: 5}), 'RIGHT')
    })

    it('should return DOWN for a destination position below the source', () => {
        assert.equal(relativeDirection({x: 5, y: 4}, {x: 5, y: 5}), 'DOWN')
    })

    it('should return UP for a destination position above the source', () => {
        assert.equal(relativeDirection({x: 9, y: 9}, {x: 9, y: 8}), 'UP')
    })
})

describe('pathRange', () => {
    const tablut = new Tablut()

    it('should return no element when source position equals destination', () => {
        assert.deepEqual([...tablut.pathRange({x: 5, y: 5}, {x: 5, y: 5})], [])
    })

    it('should return a length-1 list when source is adjacent to position', () => {
        assert.deepEqual([...tablut.pathRange({x: 1, y: 1}, {x: 2, y: 1})], [{x: 1, y: 1}])
    })

    it('should be able to compute a vertical path of size 9', () => {
        assert.deepEqual([...tablut.pathRange({x: 9, y: 1}, {x: 9, y: 9})].length, 8)
    })
})

describe('getRaichiOrTuichi', () => {
    const tablut = new Tablut()

    it('should be able to detect a RAICHI to the bottom of the board', () => {
        const board = (() => {
            const builder = boardBuilder({size: 3})
            builder.at(2, 1).wall = '1'
            builder.at(1, 2).wall = '1'
            builder.at(3, 2).wall = '1'
            return builder.board
        })()
        tablut.gamedatas = {board: board, game_options: {100: '0'}}
        assert.deepEqual(tablut.getRaichiOrTuichi({x: 2, y: 2}), ['RAICHI', [{x: 2, y: 3}]])
    })

    it('should be able to detect an horizontal TUICHI', () => {
        const board = (() => {
            const builder = boardBuilder({size: 3})
            builder.at(2, 1).wall = '1'
            builder.at(2, 3).wall = '1'
            return builder.board
        })()
        tablut.gamedatas = {board: board, game_options: {100: '0'}}
        const raichiOrTuichi = tablut.getRaichiOrTuichi({x: 2, y: 2})
        assert.equal(raichiOrTuichi[0], 'TUICHI')
        assert.equal(raichiOrTuichi[1].length, 2)
    })

    it('should be able to detect a triple TUICHI', () => {
        const board = (() => {
            const builder = boardBuilder({size: 3})
            builder.at(2, 1).wall = '1'
            return builder.board
        })()
        tablut.gamedatas = {board: board, game_options: {100: '0'}}
        const raichiOrTuichi = tablut.getRaichiOrTuichi({x: 2, y: 2})
        assert.equal(raichiOrTuichi[0], 'TUICHI')
        assert.equal(raichiOrTuichi[1].length, 3)
    })
})