{OVERALL_GAME_HEADER}

<!--
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-------
    tablut_tablut.tpl - phplib template
-->

<div id="board">
    <!-- BEGIN square -->
        <div id="square_{X}_{Y}" class="{CSS_CLASSES}" style="left: {LEFT}px; top: {TOP}px;">{TEXT}</div>
    <!-- END square -->

    <div id="discs"></div>
</div>

<script type="text/javascript"> // Templates
    var jstpl_p1Swede='<div class="p1Swede" id="disc_${x}_${y}"></div>';
    var jstpl_p1King='<div class="p1King" id="disc_${x}_${y}"></div>';
    var jstpl_p0Muscovite='<div class="p0Muscovite" id="disc_${x}_${y}"></div>';
    var jstpl_topMsg='<div id="maintitlebar_topMsg">${message}</div>';
</script>

{OVERALL_GAME_FOOTER}
