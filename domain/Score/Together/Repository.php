<?php

declare(strict_types=1);

namespace Sports\Score\Together;

use Sports\Game\Place\Together as TogetherGamePlace;

class Repository extends \Sports\Repository
{
    public function removeScores(TogetherGamePlace $gamePlace)
    {
        while ($gamePlace->getScores()->count() > 0) {
            $score = $gamePlace->getScores()->first();
            $gamePlace->getScores()->removeElement($score);
            $this->remove($score);
        }
    }
}
