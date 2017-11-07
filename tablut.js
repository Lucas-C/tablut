"use strict";

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
    "dojo",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/dom",
    "dojo/query",
    "dojo/_base/array",
    "dojo/dom-construct",
    "dojo/dom-class",
    "dojo/dom-geometry",
    "dojo/fx",
    "dojo/NodeList-data",
    "dojo/NodeList-traverse",
    "dojo/NodeList-html",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/scrollmap"
],
function (dojo, declare, lang, dom, query, array, domConstruct, domClass, domGeom, fx) {
    var SLIDE_ANIMATION_DURATION = 700;
    
    return declare("bgagame.tablut", ebg.core.gamegui, {
        constructor: function() {
        },
        
        /**
         * Sets up the game user interface according to current game situation specified
         * in parameters. Method is called each time the game interface is displayed to a player, ie:
         *  - when the game starts
         *  - when a player refreshes the game page (F5)
         */
        setup: function(datas) {
            this.setupLayout();
            this.setupPlayerCards(datas.players);
            this.setupNotifications();
        },

        setupPlayerCards: function(players) {
            this.playerData = players;

            // Base indicators
            for (var i in this.playerData) {
                if (this.playerData.hasOwnProperty(i)) {
                    var basePlayer = this.playerData[i];
                    var position = null;
                }
            }

            // Icons and hand cards
            for (var id in players) {
                if (players.hasOwnProperty(id)) {
                    var player = players[id];
                    // ...
                }
            }
        },

        setupLayout: function() {
            this.updateUi();
            dojo.connect(this, 'onGameUiWidthChange', this, lang.hitch(this, this.updateUi));
            query('#move').on('click', lang.hitch(this, this.onMove));
            this.addTooltip('move', _('Move'), '');
        },

        ///////////////////////////////////////////////////
        //// Game & client states
        onEnteringState: function(stateName, event) {
            switch (stateName) {
            }
        },

        ///////////////////////////////////////////////////
        // onLeavingState: this method is called each time we are leaving a game state.
        onLeavingState: function(stateName) {
            switch (stateName) {
            }
        },

        ///////////////////////////////////////////////////
        //// DOM Node Utility methods

        ///////////////////////////////////////////////////
        //// Animation Utility methods
        prepareForAnimation: function(node) {
            if (!node) {
                throw new Error("Must provide a node");
            }
            return query(node)
                .style('zIndex', 100)
                .style('position', 'absolute')
                .pop();
        },

        recoverFromAnimation: function(node) {
            if (!node) {
                throw new Error("Must provide a node");
            }
            return query(node)
                .style('zIndex', null)
                .style('position', null)
                .style('left', null)
                .style('top', null)
                .pop();
        },

        getCentredPosition: function(from, target) {
            var fromBox = domGeom.position(from);
            var targetBox = domGeom.position(target);
            return {
                x: targetBox.x + (targetBox.w / 2) - (fromBox.w / 2),
                y: targetBox.y + (targetBox.h / 2) - (fromBox.h / 2)
            };
        },


        ///////////////////////////////////////////////////
        //// Player's action
        onMove: function(e) {
            e.preventDefault();
            this.ajaxcall(
                "/tablut/tablut/moveTo.html",
                {
                    lock: true,
                    squareId: 0
                },
                this,
                function onSuccess() {
                },
                function onFailure() {}
            );
        },

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications
        setupNotifications: function() {
            dojo.subscribe('newScores', lang.hitch(this, this.notif_newScores));
            dojo.subscribe('endOfGame', this, function() {});
            // Delay end of game for interface stock stability before switching to game result
            this.notifqueue.setSynchronous('endOfGame', 2000);
        },

        notif_newScores: function(notification) {
            for (var playerId in notification.args) {
                if (notification.args.hasOwnProperty(playerId)) {
                    var score = notification.args[playerId].score;
                    var scoreAux = notification.args[playerId].scoreAux;
                    this.scoreCtrl[playerId].toValue(score);
                    this.updateScoreAuxCount(playerId, scoreAux);
                }
            }
        }
   });             
});
