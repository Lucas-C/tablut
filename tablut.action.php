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

/**
 * @property BattleForHill game
 */
class action_battleforhill extends APP_GameAction
{
    public function __default()
    {
        if (self::isArg('notifwindow')) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = $this->getArg("table", AT_posint, true);
        } else {
            $this->view = "battleforhill_battleforhill";
            self::trace("Complete reinitialization of board game");
        }
    }

    public function returnToDeck()
    {
        $this->setAjaxMode();

        $joinedIds = $this->getArg('ids', AT_numberlist, true);
        $ids = F\map(explode(',', $joinedIds), function ($id) {
            return intval($id);
        });
        $this->game->returnToDeck($ids);

        $this->ajaxResponse();
    }

    public function playCard()
    {
        $this->setAjaxMode();

        $cardId = $this->getArg('id', AT_int, true);
        $x = (int) $this->getArg('x', AT_int, true);
        $y = (int) $this->getArg('y', AT_int, true);
        $this->game->playCard($cardId, $x, $y);

        $this->ajaxResponse();
    }

    public function chooseAttack()
    {
        $this->setAjaxMode();

        $x = (int) $this->getArg('x', AT_int, true);
        $y = (int) $this->getArg('y', AT_int, true);
        $this->game->chooseAttack($x, $y);

        $this->ajaxResponse();
    }
}
