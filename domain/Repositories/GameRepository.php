<?php

declare(strict_types=1);

namespace Sports\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use League\Period\Period;
use Sports\Competition;
use Sports\Game\Against as AgainstGame;
use Sports\Game\State as GameState;
use Sports\Game\Together as TogetherGame;

/**
 * @template T
 * @template-extends EntityRepository<T>
 */
abstract class GameRepository extends EntityRepository
{
    /**
     * @param Competition $competition
     * @param list<GameState>|null $gameStates
     * @param int|null $gameRoundNumber
     * @param Period|null $period
     * @return QueryBuilder
     * @throws \Exception
     */
    protected function getCompetitionGamesQuery(
        Competition $competition,
    ): QueryBuilder {
        return $this->createQueryBuilder('g')
            ->join("g.poule", "p")
            ->join("p.round", "r")
            ->join("r.structureCell", "sc")
            ->join("sc.roundNumber", "rn")
            ->where('rn.competition = :competition')
            ->setParameter('competition', $competition);
    }


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

    public function customSave(AgainstGame|TogetherGame $game, bool $onlyFlushObject = false): void
    {
        $this->getEntityManager()->persist($game);
        if( $onlyFlushObject ) {
            $this->getEntityManager()->flush();
        }
    }
}
