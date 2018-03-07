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
if (typeof define === 'undefined' && typeof exports !== 'undefined') {
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
        exports.tablut = main(...depsPaths.map((path) => depsMocks[path]));
    };
    ebg = { core: { gamegui: null } };
}

define([
    'dojo',
    'dojo/_base/declare',
    'dojo/_base/lang',
    'dojo/dom',
    'dojo/query',
    'dojo/dom-geometry',
    /* Unused:
    'dojo/dom-construct',
    'dojo/dom-class',
    'dojo/NodeList-data',
    'dojo/NodeList-traverse',
    'dojo/NodeList-html',
    'dojo/_base/array',
    'dojo/fx',
    'ebg/core/gamegui',
    'ebg/counter',
    'ebg/scrollmap',//*/
], function main(dojo, declare, lang, dom, query, domGeom) {
    const ANIMATION_ZINDEX = 100;
    const END_OF_GAME_DELAY = 2000;

    return declare('bgagame.tablut', ebg.core.gamegui, {
        constructor(boardLineLength) {
            this.boardLineLength = boardLineLength || 9;
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
            console.log('setupLayout', this.gamedatas);
            const myPlayerIndex = this.gamedatas.players[this.gamedatas.playerorder[0]].color === 'ffffff' ? 1 : 0;
            console.log('myPlayerIndex:', myPlayerIndex);

            for (const pawn of this.gamedatas.board) {
                if (pawn.player !== null) {
                    this.placePawn(pawn);
                }
            }

            if (myPlayerIndex === 1) {
                dojo.query('.p1Swede').on('click', lang.hitch(this, this.onSelectPawn));
                dojo.query('.p1King').on('click', lang.hitch(this, this.onSelectPawn));
            } else {
                dojo.query('.p0Muscovite').on('click', lang.hitch(this, this.onSelectPawn));
            }
            dojo.query('.square').on('click', lang.hitch(this, this.onMove));
            this.addTooltip('move', _('Move'), '');
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
            console.log('movePawn', fromDiscId, toSquareId);
            const coords = toSquareId.split('_');
            const x = coords[1];
            const y = coords[2];
            const newDiscId = `disc_${ x }_${ y }`;
            dojo.query(`#${ fromDiscId }`).attr('id', newDiscId);
            this.slideToObject(newDiscId, toSquareId).play();
        },


        // /////////////////////////////////////////////////
        // // Animation Utility methods
        prepareForAnimation(node) {
            if (!node) {
                throw new Error('Must provide a node');
            }
            return query(node)
                .style('zIndex', ANIMATION_ZINDEX)
                .style('position', 'absolute')
                .pop();
        },

        recoverFromAnimation(node) {
            if (!node) {
                throw new Error('Must provide a node');
            }
            return query(node)
                .style('zIndex', null)
                .style('position', null)
                .style('left', null)
                .style('top', null)
                .pop();
        },

        getCentredPosition(from, target) {
            const fromBox = domGeom.position(from);
            const targetBox = domGeom.position(target);
            return {
                x: targetBox.x + (targetBox.w / 2) - (fromBox.w / 2),
                y: targetBox.y + (targetBox.h / 2) - (fromBox.h / 2),
            };
        },

        // /////////////////////////////////////////////////
        // function to remove the display of all available move
        removeAllAvailableMoves() {
            let vElement = null;
            for (vElement of this.gamedatas.board) {
                dojo.query(`#square_${ vElement.x }_${ vElement.y }`)[0].classList.remove('availableMove');
            }
        },

        // /////////////////////////////////////////////////
        // generator function, not supported by IE <= 11
        * listAvailableMoves(board, selectedDiscId) {
            const coords = selectedDiscId.split('_');
            const vDiscPosition = { x: coords[1], y: coords[2] };

            // find the element present on the table
            const vElementDisc = board.find((vElement) =>
                vElement.x === vDiscPosition.x && vElement.y === vDiscPosition.y);

            const vStart = [
                (vDiscPosition.x * this.boardLineLength) - (this.boardLineLength - vDiscPosition.y) - 2,
                (vDiscPosition.x * this.boardLineLength) - (this.boardLineLength - vDiscPosition.y),
                (vDiscPosition.x * this.boardLineLength) - (this.boardLineLength - vDiscPosition.y) - 1 - this.boardLineLength,
                (vDiscPosition.x * this.boardLineLength) - (this.boardLineLength - vDiscPosition.y) - 1 + this.boardLineLength,
            ];
            const vEnd = [
                ((vDiscPosition.x - 1) * this.boardLineLength) - 1,
                vDiscPosition.x * this.boardLineLength,
                0,
                board.length,
            ];
            /* eslint no-magic-numbers: "off" */
            const vIncrement = [ -1, 1, -this.boardLineLength, this.boardLineLength ];

            for (let vDirection = 0; vDirection < vIncrement.length; ++vDirection) {
                // initialize the default position
                let vDiscOnWall = vElementDisc.wall;

                // loop for each direction
                for (let vIndex = vStart[vDirection], vCoeff = Math.sign(vIncrement[vDirection]);
                    vIndex * vCoeff < vEnd[vDirection] * vCoeff;
                    vIndex += vIncrement[vDirection]
                ) {
                    const vPosition = board[vIndex];
                    if (!vPosition) {
                        break;
                    }
                    if (vPosition.player !== null) {
                        break;
                    }
                    if (vPosition.wall === '1' && vDiscOnWall !== '1') {
                        break;
                    }
                    yield vPosition;
                    vDiscOnWall = vPosition.wall;
                }
            }
        },


        // /////////////////////////////////////////////////
        // // Player's action
        onSelectPawn(event) {
            if (!event) {
                console.log('Unexpected empty event');
                return;
            }
            event.preventDefault();
            dojo.stopEvent(event);

            if (this.selectedDisc) {
                this.selectedDisc.classList.remove('selected');
                // remove all the availableMode
                this.removeAllAvailableMoves();
            }
            if (event.currentTarget === this.selectedDisc) {
                // unselect:
                this.selectedDisc = null;
            } else {
                this.selectedDisc = event.currentTarget;
                this.selectedDisc.classList.add('selected');
                // Display possible all available move
                for (const vPosition of this.listAvailableMoves(this.gamedatas.board, this.selectedDisc.id)) {
                    dojo.query(`#square_${ vPosition.x }_${ vPosition.y }`)[0].classList.add('availableMove');
                }
            }
        },

        onMove(event) {
            if (!event) {
                console.log('Unexpected empty event');
                return;
            }
            event.preventDefault();
            dojo.stopEvent(event);
            if (!this.selectedDisc) {
                console.log('No disc selected, doing nothing');
                return;
            }

            let coords = this.selectedDisc.id.split('_');
            const fromPos = { x: coords[1], y: coords[2] };
            coords = event.currentTarget.id.split('_');
            const toPos = { x: coords[1], y: coords[2] };
            console.log('onMove', fromPos, toPos);

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
                this.removeAllAvailableMove();
            }
        },


        // /////////////////////////////////////////////////
        // // Reaction to cometD notifications
        setupNotifications() {
            dojo.subscribe('pawnMoved', this, 'notifPawnMoved');
            dojo.subscribe('pawnEaten', this, 'notifPawnEaten');
            // dojo.subscribe('endOfGame', this, 'notifEndOfGame');
            // Delay end of game for interface stock stability before switching to game result
            this.notifqueue.setSynchronous('endOfGame', END_OF_GAME_DELAY);
        },

        notifPawnMoved(notif) {
            this.gamedatas.board = notif.args.gamedatas.board;
            this.movePawn(notif.args.fromDiscId, notif.args.toSquareId);
        },

        notifPawnEaten(notif) {
            this.gamedatas.board = notif.args.gamedatas.board;
            const discId = `disc_${ notif.args.eatenPawnX }_${ notif.args.eatenPawnY }`;
            dojo.destroy(discId);
        },
    });
});
