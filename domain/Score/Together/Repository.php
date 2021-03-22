<?php
declare(strict_types=1);

namespace Sports\Score\Together;

use Doctrine\ORM\EntityRepository;
use Sports\Game\Place\Together as TogetherGamePlace;

/**
 * @template-extends EntityRepository<TogetherGamePlace>
 */
class Repository extends EntityRepository
{
    use \Sports\Repository;

    public function removeScores(TogetherGamePlace $gamePlace): void
    {
        while ($score = $gamePlace->getScores()->first()) {
            $gamePlace->getScores()->removeElement($score);
            $this->remove($score);
        }
    }
}
