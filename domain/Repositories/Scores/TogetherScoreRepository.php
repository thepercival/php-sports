<?php

declare(strict_types=1);

namespace Sports\Repositories\Scores;

use Doctrine\ORM\EntityRepository;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Score\TogetherScore as TogetherScore;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<TogetherScore>
 */
class TogetherScoreRepository extends EntityRepository
{
    /**
     * @use BaseRepository<TogetherScore>
     */
    use BaseRepository;

    public function removeScores(TogetherGamePlace $gamePlace): void
    {
        while ($score = $gamePlace->getScores()->first()) {
            $gamePlace->getScores()->removeElement($score);
            $this->remove($score);
        }
    }
}
