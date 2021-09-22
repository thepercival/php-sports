<?php
declare(strict_types=1);

namespace Sports\Score\Together;

use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Score\Together as TogetherScore;
use Sports\Game\Place\Together as TogetherGamePlace;

/**
 * @template-extends EntityRepository<TogetherScore>
 */
class Repository extends EntityRepository
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
