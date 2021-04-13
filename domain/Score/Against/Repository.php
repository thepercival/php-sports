<?php
declare(strict_types=1);

namespace Sports\Score\Against;

use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Game\Against as AgainstGame;
use Sports\Score\Against as AgainstScore;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<AgainstScore>
 * @template-implements SaveRemoveRepository<AgainstScore>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
    use BaseRepository;

    public function removeScores(AgainstGame $game): void
    {
        while ($gameScore = $game->getScores()->first()) {
            $game->getScores()->removeElement($gameScore);
            $this->remove($gameScore);
        }
    }
}
