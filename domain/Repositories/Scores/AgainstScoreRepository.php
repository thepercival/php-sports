<?php

declare(strict_types=1);

namespace Sports\Repositories\Scores;

use Doctrine\ORM\EntityRepository;
use Sports\Game\Against as AgainstGame;
use Sports\Score\AgainstScore as AgainstScore;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<AgainstScore>
 */
class AgainstScoreRepository extends EntityRepository
{
    /**
     * @use BaseRepository<AgainstScore>
     */
    use BaseRepository;

    public function removeScores(AgainstGame $game): void
    {
        while ($gameScore = $game->getScores()->first()) {
            $game->getScores()->removeElement($gameScore);
            $this->remove($gameScore);
        }
    }
}
