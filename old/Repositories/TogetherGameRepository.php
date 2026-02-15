<?php

declare(strict_types=1);

namespace old\Repositories;

use Doctrine\ORM\QueryBuilder;
use League\Period\Period;
use Sports\Competition;
use Sports\Game\Against as AgainstGame;
use Sports\Game\State as GameState;
use Sports\Game\Together as TogetherGame;

/**
 * @template-extends GameRepository<TogetherGame>
 */
final class TogetherGameRepository extends GameRepository
{
    /**
     * @param Competition $competition
     * @param list<GameState>|null $gameStates
     * @param int|null $gameRoundNumber
     * @param Period|null $period
     * @return list<TogetherGame>
     */
    public function getCompetitionGames(
        Competition $competition,
        array $gameStates = null,
        /*int $gameRoundNumber = null,*/
        Period $period = null,
        int $maxResults = null
    ): array {
        $qb = $this->getCompetitionGamesQuery($competition);
        $qb = $this->applyExtraFilters($qb, $gameStates, $period);

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
     * @param Period|null $period
     * @return bool
     */
    public function hasCompetitionGames(
        Competition $competition,
        array $gameStates = null,
        Period $period = null
    ): bool {
        $qb = $this->getCompetitionGamesQuery($competition);
        $qb = $this->applyExtraFilters($qb, $gameStates, $period);
        /** @var list<AgainstGame> $games */
        $games = $qb->setMaxResults(1)->getQuery()->getResult();
        return count($games) === 1;
    }

    /**
     * @param QueryBuilder $query
     * @param list<GameState>|null $gameStates
     * @param Period|null $period
     * @return QueryBuilder
     * @throws \Exception
     */
    protected function applyExtraFilters(
        QueryBuilder $query,
        array $gameStates = null,
        Period $period = null
    ): QueryBuilder {
        if ($gameStates !== null) {
            $query = $query
                ->andWhere('g.state IN(:gamestates)')
                ->setParameter('gamestates', array_map(fn(GameState $gameState) => $gameState->value, $gameStates));
        }
//        if ($gameRoundNumber !== null) {
//            $query = $query
//                ->andWhere('g.gameRoundNumber = :gameRoundNumber')
//                ->setParameter('gameRoundNumber', $gameRoundNumber);
//        }
        if ($period !== null) {
            $query = $query
                ->andWhere('g.startDateTime < :end')
                ->andWhere('g.startDateTime >= :start')
                ->setParameter('end', $period->endDate)
                ->setParameter('start', $period->startDate);
        }
        return $query;
    }
}
