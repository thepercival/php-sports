<?php
declare(strict_types=1);

namespace Sports\Game;

use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Sports\Round\Number as RoundNumber;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place as GamePlace;
use Sports\Game\Together as TogetherGame;
use Sports\Competition;
use League\Period\Period;

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

        $this->_em->remove($game);
        $this->_em->flush();
    }

    public function customSave(AgainstGame|TogetherGame $game): void
    {
        $this->_em->persist($game);
        $this->_em->flush();
    }
}
