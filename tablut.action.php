<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * -----
 *
 * tablut.action.php
 *
 * Tablut main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/tablut/tablut/myAction.html", ...)
 *
 */


use Functional as F;

class action_tablut extends APP_GameAction  // @codingStandardsIgnoreLine
{
    public function __default()  // @codingStandardsIgnoreLine
    {
        if (self::isArg('notifwindow')) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = $this->getArg("table", AT_posint, true);
        } else {
            $this->view = "tablut_tablut";
            self::trace("Complete reinitialization of board game");
        }
    }

    public function move()
    {
        self::setAjaxMode();
        $fromDiscId = self::getArg("fromDiscId", AT_alphanum, true);
        $toSquareId = self::getArg("toSquareId", AT_alphanum, true);
        $result = $this->game->move($fromDiscId, $toSquareId);
        self::ajaxResponse();
    }
}
