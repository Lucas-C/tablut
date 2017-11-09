/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * tablut implementation : © Gregory Isabelli <gisabelli@boardgamearena.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * tablut.js
 *
 * tablut user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */
 
define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.tablut", ebg.core.gamegui, {
        constructor: function(){
            console.log('tablut constructor');
             
        },
        setup: function( gamedatas )
        {
            console.log( "start creating player boards" );
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                

            }

            for( var i in gamedatas.board )
            {
                var square = gamedatas.board[i];
                
                if( square.player !== null )
                {
                    this.addDiscOnBoard( square.x, square.y, square.player );
                }
            }
            
            dojo.query( '.square' ).connect( 'onclick', this, 'onPlayDisc' );            
 
            this.setupNotifications();
            
            this.ensureSpecificImageLoading( ['../common/point.png'] );
   
        },
        
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        onEnteringState: function( stateName, args )
        {
           console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            case 'playerTurn':
                this.updatePossibleMoves( args.args.possibleMoves );
                break;
            }
        },
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
             
          //  switch( stateName )
          //  {
          //  case 'playerTurn':
              
          //      break;
          //  }                
        }, 
        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
/*                case 'example':
                    this.addActionButton( 'example_1', 'bla bla1', 'onExample1' ); 
                    this.addActionButton( 'example_2', 'bla bla2', 'onExample2' ); 
                    this.addActionButton( 'example_3', 'bla bla3', 'onExample3' ); 
                    break;
*/
                }
            }
        },     

        ///////////////////////////////////////////////////
        //// Utility functions
        
        addDiscOnBoard: function( x, y, player )
        {
            var color = this.gamedatas.players[ player ].color;
            
            dojo.place( this.format_block( 'jstpl_disc', {
                xy: x+''+y,
                color: color
            } ) , 'discs' );
            
            this.placeOnObject( 'disc_'+x+''+y, 'overall_player_board_'+player );
            this.slideToObject( 'disc_'+x+''+y, 'square_'+x+'_'+y ).play();
        },   
        
        updatePossibleMoves: function( possibleMoves )
        {
            // Remove current possible moves
            dojo.query( '.possibleMove' ).removeClass( 'possibleMove' );

            for( var x in possibleMoves )
            {
                for( var y in possibleMoves[ x ] )
                {
                    // x,y is a possible move
                    dojo.addClass( 'square_'+x+'_'+y, 'possibleMove' );
                }            
            }
                        
            this.addTooltipToClass( 'possibleMove', '', _('Place a disc here') );
        },

        ///////////////////////////////////////////////////
        //// Player's action
        
        onPlayDisc: function( evt )
        {
            // Stop this event propagation
            evt.preventDefault();
            dojo.stopEvent( evt );

            // Get the cliqued square x and y
            // Note: square id format is "square_X_Y"
            var coords = evt.currentTarget.id.split('_');
            var x = coords[1];
            var y = coords[2];

            if( ! dojo.hasClass( 'square_'+x+'_'+y, 'possibleMove' ) )
            {
                // This is not a possible move => the click does nothing
                return ;
            }
            
            if( this.checkAction( 'playDisc' ) )    // Check that this action is possible at this moment
            {            
                this.ajaxcall( "/tablut/tablut/playDisc.html", {
                    x:x,
                    y:y
                }, this, function( result ) {} );
            }            
        },

        
        ///////////////////////////////////////////////////
        //// Reaction to game notifications

        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            dojo.subscribe( 'playDisc', this, "notif_playDisc" );
            this.notifqueue.setSynchronous( 'playDisc', 500 );
            dojo.subscribe( 'turnOverDiscs', this, "notif_turnOverDiscs" );
            this.notifqueue.setSynchronous( 'turnOverDiscs', 1500 );
            
            dojo.subscribe( 'newScores', this, "notif_newScores" );
        },
        
        notif_playDisc: function( notif )
        {
            // Remove current possible moves (makes the board more clear)
            dojo.query( '.possibleMove' ).removeClass( 'possibleMove' );        
        
            this.addDiscOnBoard( notif.args.x, notif.args.y, notif.args.player_id );
        },
        
        notif_turnOverDiscs: function( notif )
        {
            // Get the color of the player who is returning the discs
            var targetColor = this.gamedatas.players[ notif.args.player_id ].color;

            // Made these discs blinking and set them to the specified color
            for( var i in notif.args.turnedOver )
            {
                var disc = notif.args.turnedOver[ i ];
                
                // Make the disc blink 2 times
                var anim = dojo.fx.chain( [
                    dojo.fadeOut( { node: 'disc_'+disc.x+''+disc.y } ),
                    dojo.fadeIn( { node: 'disc_'+disc.x+''+disc.y } ),
                    dojo.fadeOut( { 
                                    node: 'disc_'+disc.x+''+disc.y,
                                    onEnd: function( node ) {

                                        // Remove any color class
                                        dojo.removeClass( node, [ 'disccolor_000000', 'disccolor_ffffff' ] );
                                        // ... and add the good one
                                        dojo.addClass( node, 'disccolor_'+targetColor );
                                                             
                                    } 
                                  } ),
                    dojo.fadeIn( { node: 'disc_'+disc.x+''+disc.y  } )
                                 
                ] ); // end of dojo.fx.chain

                // ... and launch the animation
                anim.play();                
            }
        },
        notif_newScores: function( notif )
        {
            for( var player_id in notif.args.scores )
            {
                var newScore = notif.args.scores[ player_id ];
                this.scoreCtrl[ player_id ].toValue( newScore );
            }
        }
   });             
});


