/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * -----
 *
 * tablut.js
 *
 * Tablut user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 * Available JS functions reference: http://en.doc.boardgamearena.com/Studio_function_reference
 *
 * Note: this === window.gameui
 */

// If `define` does not exist and we are in a NodeJS module context (e.g. for unit tests),
// mock `define` and the dojo dependencies
if (typeof define === 'undefined' && typeof module !== 'undefined') {
    const depsMocks = {
        'dojo/_base/declare': (className, superClass, props) => {
            function dojoClass(...args) {
                /* eslint no-invalid-this: "off" */
                if (props.constructor) {
                    props.constructor.apply(this, args);
                }
            }
            dojoClass.prototype = props;
            return dojoClass;
        },
    };
    // Globals:
    define = function define(depsPaths, main) {
        module.exports = main(...depsPaths.map((path) => depsMocks[path]));
    };
    ebg = { core: { gamegui: null } };
}

define([
    'dojo',
    'dojo/_base/declare',
    'dojo/_base/lang',
    // dojo extension providing .removeClass:
    'dojo/NodeList-dom',
    // Unused but required to define global `ebg.core.gamegui`:
    'ebg/core/gamegui', 'ebg/counter',
    /* Unused but available:
    'dojo/dom',
    'dojo/dom-construct',
    'dojo/dom-class',
    'dojo/dom-geometry',
    'dojo/fx',
    'dojo/query',
    'dojo/NodeList-data',
    'dojo/NodeList-traverse',
    'dojo/NodeList-html',
    'dojo/_base/array',
    'ebg/scrollmap',//*/
], function main(dojo, declare, lang) {
    const END_OF_GAME_DELAY = 2000;
    const DIRECTIONS = [ 'LEFT', 'RIGHT', 'DOWN', 'UP' ];
    const POS_DELTA_PER_DIR = {
        LEFT: { x: -1, y: 0 },
        RIGHT: { x: 1, y: 0 },
        DOWN: { x: 0, y: 1 },
        UP: { x: 0, y: -1 },
    };

    return declare('bgagame.tablut', ebg.core.gamegui, {
        constructor() {
        },

        /**
         * Sets up the game user interface according to current game situation specified
         * in parameters. Method is called each time the game interface is displayed to a player, ie:
         *  - when the game starts
         *  - when a player refreshes the game page (F5)
         */
        setup() {
            this.setupLayout();
            this.setupNotifications();
        },

        setupLayout() {
            const myPlayerIndex = this.gamedatas.players[this.gamedatas.playerorder[0]].color === 'ffffff' ? 1 : 0;

            for (const pawn of this.gamedatas.board) {
                if (pawn.player !== null) {
                    this.placePawn(pawn);
                }
            }

            if (myPlayerIndex === 1) {
                dojo.query('.p1Swede').addClass('selectable').on('click', lang.hitch(this, this.onSelectPawn));
                dojo.query('.p1King').addClass('selectable').on('click', lang.hitch(this, this.onSelectPawn));
            } else {
                dojo.query('.p0Muscovite').addClass('selectable').on('click', lang.hitch(this, this.onSelectPawn));
            }
            dojo.query('.square').on('click', lang.hitch(this, this.onMove));
            if (this.isRuleVariant()) {
                this.addTooltipToClass('fortress', _('Only the king can enter the fortressses !'), '');
            } else {
                this.addTooltipToClass('fortress', _('No one can enter the fortresses !'), '');
            }
            if (this.isRuleVariant()) {
                this.addTooltipToClass('throne', _('Apart from the king, pawns cannot stop on the throne'), '');
            } else {
                this.addTooltipToClass('throne', _('Only the king can pass on the throne'), '');
            }
            this.displayRaichiTuichi();
        },

        isRuleVariant() {
            return this.gamedatas.game_options && this.gamedatas.game_options[100] === '1';
        },


        // /////////////////////////////////////////////////
        // // Game & client states
        onEnteringState(stateName) {
            switch (stateName) {
            case 'playerTurn':
                break;
            default:
                break;
            }
        },

        onLeavingState() {
        },


        // /////////////////////////////////////////////////
        // // Utility functions

        placePawn(pawn) {
            const pawnPlayerIndex = this.gamedatas.players[pawn.player].color === 'ffffff' ? 1 : 0;
            if (pawn.king) {
                dojo.place(this.format_block('jstpl_p1King', {
                    x: pawn.x,
                    y: pawn.y,
                }), 'discs');
            } else if (pawnPlayerIndex === 1) {
                dojo.place(this.format_block('jstpl_p1Swede', {
                    x: pawn.x,
                    y: pawn.y,
                }), 'discs');
            } else {
                dojo.place(this.format_block('jstpl_p0Muscovite', {
                    x: pawn.x,
                    y: pawn.y,
                }), 'discs');
            }
            // this.placeOnObject(String(`disc_${x}_${y}`), `overall_player_board_${player}`);
            this.slideToObject(`disc_${ pawn.x }_${ pawn.y }`, `square_${ pawn.x }_${ pawn.y }`).play();
        },

        movePawn(fromDiscId, toSquareId) {
            const coords = toSquareId.split('_');
            const x = coords[1];
            const y = coords[2];
            const newDiscId = `disc_${ x }_${ y }`;
            dojo.query(`#${ fromDiscId }`).attr('id', newDiscId);
            this.slideToObject(newDiscId, toSquareId).play();
        },

        // Now that notifications are sent for RAICHI / TUICHI, we could replace this by a subscriber
        displayRaichiTuichi() {
            this.clearWinningPaths();
            const king = this.getKingPos(this.gamedatas.board);
            if (!king || Number(king.limitWin)) {
                return;
            }
            const raichiOrTuichi = this.getRaichiOrTuichi(king);
            if (raichiOrTuichi) {
                const [ winningMoveName, winningPath ] = raichiOrTuichi;
                this.displayWinningPaths(winningPath);
                this.showMessage(`${ winningMoveName } !`, 'info');
            }
        },

        displayWinningPaths(winningPathPositions) {
            const maxPos = Math.sqrt(this.gamedatas.board.length);
            for (const vPosition of winningPathPositions) {
                const domElem = dojo.query(`#square_${ vPosition.x }_${ vPosition.y }`)[0];
                if (vPosition.x === 1 || vPosition.x === maxPos || vPosition.y === 1 || vPosition.y === maxPos) {
                    domElem.classList.add('finalWinningPos');
                } else {
                    domElem.classList.add('winningPath');
                }
            }
        },

        clearWinningPaths() {
            dojo.query('.finalWinningPos').removeClass('finalWinningPos');
            dojo.query('.winningPath').removeClass('winningPath');
        },

        clearAllAvailableMoves() {
            dojo.query('.availableMove').removeClass('availableMove');
        },

        getBoardElemAtPos(board, { x, y }) {
            return board.find((vElement) => Number(vElement.x) === Number(x) && Number(vElement.y) === Number(y));
        },

        getKingPos(board) {
            return board.find((vElement) => vElement.king);
        },

        // /////////////////////////////////////////////////
        // generator function, not supported by IE <= 11
        * listAvailableMoves(pawnPos) {
            const board = this.gamedatas.board;
            const boardLineLength = Math.sqrt(board.length);
            /* eslint no-magic-numbers: "off" */
            const boardIndexIncrementPerDir = {
                LEFT: -boardLineLength,
                RIGHT: boardLineLength,
                DOWN: 1,
                UP: -1,
            };

            // find the element present on the table
            const vElementDisc = this.getBoardElemAtPos(board, pawnPos);
            const isKing = vElementDisc.king;

            const vStart = {
                LEFT: (pawnPos.x * boardLineLength) - (boardLineLength - pawnPos.y) - 1 - boardLineLength,
                RIGHT: (pawnPos.x * boardLineLength) - (boardLineLength - pawnPos.y) - 1 + boardLineLength,
                DOWN: (pawnPos.x * boardLineLength) - (boardLineLength - pawnPos.y),
                UP: (pawnPos.x * boardLineLength) - (boardLineLength - pawnPos.y) - 2,
            };
            const vEnd = {
                LEFT: -1,
                RIGHT: board.length,
                DOWN: pawnPos.x * boardLineLength,
                UP: ((pawnPos.x - 1) * boardLineLength) - 1,
            };
            for (const dir of DIRECTIONS) {
                // initialize the default position
                let vDiscOnWall = vElementDisc.wall;

                // loop for each direction
                for (let vIndex = vStart[dir], dirSign = Math.sign(boardIndexIncrementPerDir[dir]);
                    vIndex * dirSign < vEnd[dir] * dirSign;
                    vIndex += boardIndexIncrementPerDir[dir]
                ) {
                    const vPosition = board[vIndex];
                    // Pawns cannot go through other pawns:
                    if (vPosition.player !== null) {
                        break;
                    }
                    // Case of the central position
                    if (vPosition.wall === '2') {
                        // The king can always pass on the throne
                        if (!isKing) {
                            // In the variant rule, the pawns can pass through it but not stop on it
                            if (this.isRuleVariant()) {
                                continue;
                            } else {
                                break;
                            }
                        }
                    // Pawns can only pass on fortresses if they started on one of them without ever stepping of
                    // Also the king can go on the corner fortresses
                    } else if (vPosition.wall === '1' && vDiscOnWall !== '1' && (!isKing || vPosition.limitWin !== '1')) {
                        break;
                    }
                    yield vPosition;
                    vDiscOnWall = vPosition.wall;
                }
            }
        },

        relativeDirection(startPos, endPos) {
            if (startPos.x === endPos.x) {
                if (startPos.y < endPos.y) {
                    return 'DOWN';
                } else if (startPos.y > endPos.y) {
                    return 'UP';
                }
                throw new Error(`Start & end positions are identical (${ startPos.x }, ${ startPos.y })`);
            } else if (startPos.y === endPos.y) {
                return startPos.x < endPos.x ? 'RIGHT' : 'LEFT';
            } else {
                throw new Error(`Start (${ startPos.x }, ${ startPos.y }) & end (${ endPos.x }, ${ endPos.y }) positions are not aligned`);
            }
        },

        * pathRange(startPos, endPos) {
            let curPos = { x: startPos.x, y: startPos.y };
            // lazy init to allow to return an empty list and not raise an exception if startPos == endPos:
            let dir = null;
            for (let i = 0; i < 10; i++) {
                if (curPos.x === endPos.x && curPos.y === endPos.y) {
                    return;
                }
                if (!dir) {
                    dir = this.relativeDirection(startPos, endPos);
                }
                yield curPos;
                curPos = { x: curPos.x + POS_DELTA_PER_DIR[dir].x, y: curPos.y + POS_DELTA_PER_DIR[dir].y };
            }
            throw new Error(`Infinite loop - Last curPos: {x: ${ curPos.x }, y: ${ curPos.y }}`);
        },

        isSquareAWinningPos(pos) {
            const maxPos = Math.sqrt(this.gamedatas.board.length);
            if (this.isRuleVariant()) {
                return (pos.x === 1 && pos.y === 1) ||
                    (pos.x === 1 && pos.y === maxPos) ||
                    (pos.x === maxPos && pos.y === maxPos) ||
                    (pos.x === maxPos && pos.y === 1);
            }
            return pos.x === 1 || pos.x === maxPos || pos.y === 1 || pos.y === maxPos;
        },

        getRaichiOrTuichi(kingPos) {
            kingPos.x = Number(kingPos.x);
            kingPos.y = Number(kingPos.y);
            const availableBorderPos = [];
            for (const vPosition of this.listAvailableMoves(kingPos)) {
                // string to integer conversion:
                vPosition.x = Number(vPosition.x);
                vPosition.y = Number(vPosition.y);
                if (this.isSquareAWinningPos(vPosition)) {
                    availableBorderPos.push(vPosition);
                }
            }
            if (availableBorderPos.length === 1) {
                return [ 'RAICHI', [ ...this.pathRange(availableBorderPos[0], kingPos) ] ];
            }
            if (availableBorderPos.length > 1) {
                const paths = availableBorderPos.map((pathEnd) => [ ...this.pathRange(pathEnd, kingPos) ]);
                return [ 'TUICHI', Array.prototype.concat(...paths) ];
            }
            return null;
        },

        // /////////////////////////////////////////////////
        // // Player's action
        onSelectPawn(event) {
            if (!event || !this.isCurrentPlayerActive()) {
                return;
            }
            event.preventDefault();
            dojo.stopEvent(event);

            if (this.selectedDisc) {
                this.selectedDisc.classList.remove('selected');
                // remove all the availableMode
                this.clearAllAvailableMoves();
            }
            if (event.currentTarget === this.selectedDisc) {
                // unselect:
                this.selectedDisc = null;
            } else {
                this.selectedDisc = event.currentTarget;
                this.selectedDisc.classList.add('selected');
                // Display possible all available move
                const coords = this.selectedDisc.id.split('_');
                const pawnPos = { x: coords[1], y: coords[2] };
                for (const vPosition of this.listAvailableMoves(pawnPos)) {
                    dojo.query(`#square_${ vPosition.x }_${ vPosition.y }`)[0].classList.add('availableMove');
                }
            }
        },

        // /////////////////////////////////////////////////////
        onMove(event) {
            if (!event || !this.isCurrentPlayerActive()) {
                return;
            }
            event.preventDefault();
            dojo.stopEvent(event);
            if (!this.selectedDisc) {
                return;
            }

            if (!event.currentTarget.classList.value.includes('availableMove')) {
                console.log('No valid move');
                return;
            }

            if (this.checkAction('move')) { // eslint-disable-line no-unreachable
                this.ajaxcall(
                    '/tablut/tablut/move.html',
                    {
                        lock: true,
                        fromDiscId: this.selectedDisc.id,
                        toSquareId: event.currentTarget.id,
                    },
                    this,
                    function noop() {},
                    function noop() {}
                );
                this.selectedDisc.classList.remove('selected');
                this.selectedDisc = null;
                this.clearAllAvailableMoves();
            }
        },


        // /////////////////////////////////////////////////
        // // Reaction to cometD notifications
        setupNotifications() {
            dojo.subscribe('pawnMoved', this, 'notifPawnMoved');
            dojo.subscribe('pawnEaten', this, 'notifPawnEaten');
            // Delay end of game for interface stock stability before switching to game result
            this.notifqueue.setSynchronous('endOfGame', END_OF_GAME_DELAY);
        },

        notifPawnMoved(notif) {
            this.gamedatas.board = notif.args.gamedatas.board;
            this.movePawn(notif.args.fromDiscId, notif.args.toSquareId);
            this.displayRaichiTuichi();
        },

        notifPawnEaten(notif) {
            this.gamedatas.board = notif.args.gamedatas.board;
            const discId = `disc_${ notif.args.eatenPawnX }_${ notif.args.eatenPawnY }`;
            this.slideToObject(discId, 'player_boards', 2000).play();
            dojo.fadeOut({ node: discId, delay:2000, onEnd: () => dojo.destroy(discId) }).play();
            this.displayRaichiTuichi();
        },
    });
});
