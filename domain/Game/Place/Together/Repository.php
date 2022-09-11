<?php

declare(strict_types=1);

namespace Sports\Game\Place\Together;

use Doctrine\ORM\QueryBuilder;
use League\Period\Period;
use Sports\Competition;
use Sports\Game\State as GameState;
use Sports\Game\Together as TogetherGame;
use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Game\Place\Together as TogetherGamePlace;

/**
 * @template-extends EntityRepository<TogetherGamePlace>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<TogetherGamePlace>
     */
    use BaseRepository;

    /**
     * @param Competition $competition
     * @param list<GameState>|null $gameStates
     * @param int|null $gameRoundNumber
     * @param Period|null $period
     * @return list<TogetherGame>
     */
    public function findByExt(
        Competition $competition,
        array $gameStates = null,
        int $gameRoundNumber = null,
        Period $period = null,
        int $maxResults = null
    ): array {
        $qb = $this->getCompetitionGamesQuery($competition, $gameStates, $gameRoundNumber, $period);
        $qb = $qb->orderBy('g.startDateTime', 'ASC');
        if ($maxResults !== null) {
            $qb->setMaxResults($maxResults);
        }
        /** @var list<TogetherGame> $games */
        $games = $qb->getQuery()->getResult();
        return $games;
    }

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
        array $gameStates = null,
        int $gameRoundNumber = null,
        Period $period = null,
    ): QueryBuilder {
        $query = $this->createQueryBuilder('tgp')
            ->join("tgp.game", "g")
            ->join("g.poule", "p")
            ->join("p.round", "r")
            ->join("r.structureCell", "sc")
            ->join("sc.roundNumber", "rn")
            ->where('rn.competition = :competition')
            ->setParameter('competition', $competition);;
        return $this->applyExtraFilters($query, $gameStates, $gameRoundNumber, $period);
    }

    /**
     * @param QueryBuilder $query
     * @param list<GameState>|null $gameStates
     * @param int|null $gameRoundNumber
     * @param Period|null $period
     * @return QueryBuilder
     * @throws \Exception
     */
    protected function applyExtraFilters(
        QueryBuilder $query,
        array $gameStates = null,
        int $gameRoundNumber = null,
        Period $period = null
    ): QueryBuilder {
        if ($gameStates !== null) {
            $query = $query
                ->andWhere('g.state IN(:gamestates)')
                ->setParameter('gamestates', array_map(fn(GameState $gameState) => $gameState->value, $gameStates));
        }
        if ($gameRoundNumber !== null) {
            $query = $query
                ->andWhere('tgp.gameRoundNumber = :gameRoundNumber')
                ->setParameter('gameRoundNumber', $gameRoundNumber);
        }
        if ($period !== null) {
            $query = $query
                ->andWhere('g.startDateTime <= :end')
                ->andWhere('g.startDateTime >= :start')
                ->setParameter('end', $period->getEndDate())
                ->setParameter('start', $period->getStartDate());
        }
        return $query;
    }
}
