<?php
declare(strict_types=1);

namespace Sports\Score\Against;

use Doctrine\ORM\EntityRepository;
use Sports\Game\Against as AgainstGame;
use Sports\Score\Against as AgainstScore;

/**
 * @template-extends EntityRepository<AgainstScore>
 */
class Repository extends EntityRepository
{
    use \Sports\Repository;

    public function removeScores(AgainstGame $game): void
    {
        while ($gameScore = $game->getScores()->first()) {
            $game->getScores()->removeElement($gameScore);
            $this->remove($gameScore);
        }
    }
}
