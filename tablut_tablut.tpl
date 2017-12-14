{OVERALL_GAME_HEADER}

<!--
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-------
    tablut_tablut.tpl

    This is the HTML template of your game.

    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.

    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format

    See your "view" PHP file to check how to set variables and control blocks

    Please REMOVE this comment before publishing your game on BGA
-->

<div class="board">
    <!-- BEGIN square -->
        <div id="square_{X}_{Y}" class="square {EXTRA_CLASS}" style="left: {LEFT}px; top: {TOP}px;"></div>
    <!-- END square -->

    <div id="discs"></div>
</div>

<script type="text/javascript"> // Templates
    var jstpl_discPlayer1='<div class="discPlayer1 disccolor" id="disc_${x}_${y}"></div>';
    var jstpl_discPlayer1King='<div class="discPlayer1King disccolor" id="disc_${x}_${y}"></div>';
    var jstpl_discPlayer0='<div class="discPlayer0 disccolor" id="disc_${x}_${y}"></div>';
</script>

{OVERALL_GAME_FOOTER}
