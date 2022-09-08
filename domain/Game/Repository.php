<?php

declare(strict_types=1);

namespace Sports\Game;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use League\Period\Period;
use Sports\Competition;
use Sports\Game\Against as AgainstGame;
use Sports\Game\State as GameState;
use Sports\Game\Together as TogetherGame;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template T
 * @template-extends EntityRepository<T>
 */
abstract class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<T>
     */
    use BaseRepository;

    /**
     * @param Competition $competition
     * @param list<GameState>|null $gameStates
     * @param int|null $gameRoundNumber
     * @param Period|null $period
     * @return bool
     */
    public function hasCompetitionGames(
        Competition $competition,
        array $gameStates = null,
        int $gameRoundNumber = null,
        Period $period = null
    ): bool {
        /** @var list<AgainstGame|TogetherGame> $games */
        $games = $this->getCompetitionGamesQuery(
            $competition,
            $gameStates,
            $gameRoundNumber,
            $period
        )->setMaxResults(1)->getQuery()->getResult();
        return count($games) === 1;
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
        Period $period = null
    ): QueryBuilder {
        $query = $this->createQueryBuilder('g')
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
                ->andWhere('g.gameRoundNumber = :gameRoundNumber')
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

    /**
     * @param T $game
     * @param bool $onlyFlushObject
     * @throws \Exception
     */
    public function customSave(mixed $game, bool $onlyFlushObject = false): void
    {
        $this->save($game, $onlyFlushObject);
    }
}
