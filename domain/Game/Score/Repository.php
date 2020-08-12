<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 15-12-17
 * Time: 12:12
 */

namespace Sports\Game\Score;

use Sports\Game;

/**
 * Game
 */
class Repository extends \Sports\Repository
{
    /**
     * @param Game $game
     */
    public function removeScores(Game $game)
    {
        while ($game->getScores()->count() > 0) {
            $gameScore = $game->getScores()->first();
            $game->getScores()->removeElement($gameScore);
            $this->remove($gameScore);
        }
    }
}
