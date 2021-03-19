<?php

declare(strict_types=1);

namespace Sports\Score\Together;

use Sports\Game\Place\Together as TogetherGamePlace;

class Repository extends \Sports\Repository
{
    public function removeScores(TogetherGamePlace $gamePlace): void
    {
        while ($score = $gamePlace->getScores()->first()) {
            $gamePlace->getScores()->removeElement($score);
            $this->remove($score);
        }
    }
}
