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
            this.setupLayout();
            this.setupPlayerCards(datas.players);
            this.setupNotifications();
        },

        setupPlayerCards(players) {
            this.playerData = players;

            // Base indicators
            for (const i in this.playerData) {
                if (Object.prototype.hasOwnProperty.call(this.playerData, i)) {
                    const basePlayer = this.playerData[i];
                    const position = null;
                }
            }

            // Icons and hand cards
            for (const id in players) {
                if (Object.prototype.hasOwnProperty.call(players, id)) {
                    const player = players[id];
                    // ...
                }
            }
        },

        setupLayout() {
            this.updateUi();
            dojo.connect(this, 'onGameUiWidthChange', this, lang.hitch(this, this.updateUi));
            query('#move').on('click', lang.hitch(this, this.onMove));
            this.addTooltip('move', _('Move'), '');
        },

        // /////////////////////////////////////////////////
        // // Game & client states
        onEnteringState(stateName, event) {
            switch (stateName) {
            }
        },

        // /////////////////////////////////////////////////
        // onLeavingState: this method is called each time we are leaving a game state.
        onLeavingState(stateName) {
            switch (stateName) {
            }
        },

        // /////////////////////////////////////////////////
        // // DOM Node Utility methods

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
            this.ajaxcall(
                '/tablut/tablut/moveTo.html',
                {
                    lock: true,
                    squareId: 0,
                },
                this,
                function onSuccess() {
                },
                function onFailure() {}
            );
        },


        // /////////////////////////////////////////////////
        // // Reaction to cometD notifications
        setupNotifications() {
            dojo.subscribe('newScores', lang.hitch(this, this.notifNewScores));
            dojo.subscribe('endOfGame', this, function notifNoop() {});
            // Delay end of game for interface stock stability before switching to game result
            this.notifqueue.setSynchronous('endOfGame', END_OF_GAME_DELAY);
        },

        notifNewScores(notification) {
            for (const playerId in notification.args) {
                if (Object.prototype.hasOwnProperty.call(notification.args, playerId)) {
                    const score = notification.args[playerId].score;
                    const scoreAux = notification.args[playerId].scoreAux;
                    this.scoreCtrl[playerId].toValue(score);
                    this.updateScoreAuxCount(playerId, scoreAux);
                }
            }
        },
    });
});
