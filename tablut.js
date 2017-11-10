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
 */

define([
    'dojo',
    'dojo/_base/declare',
    'dojo/_base/lang',
    'dojo/dom',
    'dojo/query',
    'dojo/dom-construct',
    'dojo/dom-class',
    'dojo/dom-geometry',
    // Unused:
    'dojo/NodeList-data',
    'dojo/NodeList-traverse',
    'dojo/NodeList-html',
    'dojo/_base/array',
    'dojo/fx',
    'ebg/core/gamegui',
    'ebg/counter',
    'ebg/scrollmap',
], function main(dojo, declare, lang, dom, query, domConstruct, domClass, domGeom) {
    const ANIMATION_ZINDEX = 100;
    const END_OF_GAME_DELAY = 2000;

    return declare('bgagame.tablut', ebg.core.gamegui, {
        constructor() {
        },

        /**
         * Sets up the game user interface according to current game situation specified
         * in parameters. Method is called each time the game interface is displayed to a player, ie:
         *  - when the game starts
         *  - when a player refreshes the game page (F5)
         */
        setup(datas) {
            this.setupLayout(datas);
            this.setupNotifications();
        },

        setupLayout(gamedatas) {
            console.log('setupLayout', gamedatas);
            for (const i in gamedatas.board) {
                const square = gamedatas.board[i];
                if (square.player !== null) {
                    this.placePawn(square.x, square.y);
                }
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

        placePawn(x, y) {
            dojo.place(this.format_block('jstpl_disc', {
                xy: String(x) + y,
                color: 'red',
            }), 'discs');

            // this.placeOnObject(String(`disc_${ x }`) + y, `overall_player_board_${ player }`);
            this.slideToObject(String(`disc_${ x }`) + y, `square_${ x }_${ y }`).play();
        },

        movePawn(fromSquareId, toSquareId) {
            console.log('movePawn', fromSquareId, toSquareId);
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
        // // Player's action
        onMove(event) {
            event.preventDefault();
            dojo.stopEvent(event);

            // Get the cliqued square x and y
            // Note: square id format is "square_X_Y"
            const coords = event.currentTarget.id.split('_');
            console.log('onMove', coords);
            const x = coords[1];
            const y = coords[2];

            this.placePawn(x, y);
            return;

            if (this.checkAction('move')) { // eslint-disable-line no-unreachable
                this.ajaxcall(
                    '/tablut/tablut/move.html',
                    {
                        lock: true,
                        fromSquareId: 0,
                        toSquareId: 0,
                    },
                    this,
                    function onSuccess() {
                    },
                    function onFailure() {}
                );
            }
        },


        // /////////////////////////////////////////////////
        // // Reaction to cometD notifications
        setupNotifications() {
            dojo.subscribe('playerMoved', this, 'notifPlayerMoved');
            dojo.subscribe('endOfGame', this, function notifNoop() {});
            // Delay end of game for interface stock stability before switching to game result
            this.notifqueue.setSynchronous('endOfGame', END_OF_GAME_DELAY);
        },

        notifPlayerMoved(notif) {
            this.movePawn(notif.args.fromSquareId, notif.args.toSquareId);
        },
    });
});
