<?php

declare(strict_types=1);

namespace old\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\Game\Against as AgainstGame;
use Sports\Score\Against as AgainstScore;

/**
 * @template-extends EntityRepository<AgainstScore>
 */
final class AgainstScoreRepository extends EntityRepository
{
    public function removeScores(AgainstGame $game): void
    {
        while ($gameScore = $game->getScores()->first()) {
            $game->getScores()->removeElement($gameScore);
            $this->getEntityManager()->remove($gameScore);
        }
        $this->getEntityManager()->flush();
    }
}
