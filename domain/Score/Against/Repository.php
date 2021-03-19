<?php
declare(strict_types=1);

namespace Sports\Score\Against;

use Sports\Game\Against as AgainstGame;

class Repository extends \Sports\Repository
{
    public function removeScores(AgainstGame $game): void
    {
        while ($gameScore = $game->getScores()->first()) {
            $game->getScores()->removeElement($gameScore);
            $this->remove($gameScore);
        }
    }
}
