<?php

declare(strict_types=1);

namespace Sports\Game;

use Doctrine\ORM\EntityRepository;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Together as TogetherGame;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template T
 * @template-extends EntityRepository<T>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<T>
     */
    use BaseRepository;

    public function customRemove(AgainstGame|TogetherGame $game): void
    {
        if ($game instanceof AgainstGame) {
            $game->getPoule()->getAgainstGames()->removeElement($game);
        } else {
            $game->getPoule()->getTogetherGames()->removeElement($game);
        }

        $this->getEntityManager()->remove($game);
        $this->getEntityManager()->flush();
    }

    public function customSave(AgainstGame|TogetherGame $game): void
    {
        $this->getEntityManager()->persist($game);
        $this->getEntityManager()->flush();
    }
}
