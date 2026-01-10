<?php

declare(strict_types=1);

namespace Sports\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Score\Together as TogetherScore;

/**
 * @template-extends EntityRepository<TogetherScore>
 */
final class TogetherScoreRepository extends EntityRepository
{
    public function removeScores(TogetherGamePlace $gamePlace): void
    {
        while ($score = $gamePlace->getScores()->first()) {
            $gamePlace->getScores()->removeElement($score);
            $this->getEntityManager()->remove($score);
        }
    }
}
