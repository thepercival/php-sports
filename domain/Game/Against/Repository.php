<?php

declare(strict_types=1);

namespace Sports\Game\Against;

use Doctrine\ORM\QueryBuilder;
use League\Period\Period;
use Sports\Competition;
use Sports\Competitor;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\Repository as GameRepository;
use Sports\Game\State as GameState;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\Against\Side as AgainstSide;

/**
 * @template-extends GameRepository<AgainstGame>
 */
class Repository extends GameRepository
{
    public function findOneByExt(Competitor $homeCompetitor, Competitor $awayCompetitor, Period $period): ?AgainstGame
    {
        $exprHome = $this->getEntityManager()->getExpressionBuilder();
        $exprAway = $this->getEntityManager()->getExpressionBuilder();

        $query = $this->createQueryBuilder('g')
            ->where('g.startDateTime >= :start')
            ->andWhere('g.startDateTime <= :end')
            ->andWhere(
                $exprHome->exists(
                    $this->getEntityManager()->createQueryBuilder()
                        ->select('gpphome.id')
                        ->from('Sports\Game\Place', 'gpphome')
                        ->join("gpphome.place", "pphome")
                        ->where('gpphome.game = g')
                        ->andWhere('gpphome.side = :home')
                        ->andWhere('pphome.competitor = :homecompetitor')
                        ->getDQL()
                )
            )
            ->andWhere(
                $exprAway->exists(
                    $this->getEntityManager()->createQueryBuilder()
                        ->select('gppaway.id')
                        ->from('Sports\Game\Place', 'gppaway')
                        ->join("gppaway.place", "ppaway")
                        ->where('gppaway.game = g')
                        ->andWhere('gppaway.side = :away')
                        ->andWhere('ppaway.competitor = :awaycompetitor')
                        ->getDQL()
                )
            );
        $query = $query->setParameter('home', AgainstSide::Home);
        $query = $query->setParameter('homecompetitor', $homeCompetitor);
        $query = $query->setParameter('away', AgainstSide::Away);
        $query = $query->setParameter('awaycompetitor', $awayCompetitor);
        $query = $this->applyExtraFilters($query, null, null, $period);
        /** @var list<AgainstGame> $games */
        $games = $query->getQuery()->getResult();
        $firstGame = reset($games);
        return $firstGame !== false ? $firstGame : null;
    }

    /**
     * @param Competition $competition
     * @param list<GameState>|null $gameStates
     * @param int|null $gameRoundNumber
     * @param Period|null $period
     * @return list<AgainstGame>
     */
    public function getCompetitionGames(
        Competition $competition,
        array $gameStates = null,
        int $gameRoundNumber = null,
        Period $period = null
    ): array {
        $qb = $this->getCompetitionGamesQuery($competition, $gameStates, $gameRoundNumber, $period);
        $qb = $qb->orderBy('g.startDateTime', 'ASC');
        /** @var list<AgainstGame> $games */
        $games = $qb->getQuery()->getResult();
        return $games;
    }

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
        /** @var list<AgainstGamePlace> $games */
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
     * @return int
     */
    public function getNrOfCompetitionGamePlaces(
        Competition $competition,
        array $gameStates = null,
        int $gameRoundNumber = null,
        Period $period = null
    ): int {
        /** @var list<AgainstGamePlace> $gamePlaces */
        $gamePlaces = $this->getCompetitionGamePlacessQuery(
            $competition,
            $gameStates,
            $gameRoundNumber,
            $period
        )->getQuery()->getResult();
        return count($gamePlaces);
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
            ->setParameter('competition', $competition);
        ;
        return $this->applyExtraFilters($query, $gameStates, $gameRoundNumber, $period);
    }

    /**
     * @param Competition $competition
     * @param list<GameState>|null $gameStates
     * @param int|null $gameRoundNumber
     * @param Period|null $period
     * @return QueryBuilder
     * @throws \Exception
     */
    protected function getCompetitionGamePlacessQuery(
        Competition $competition,
        array $gameStates = null,
        int $gameRoundNumber = null,
        Period $period = null
    ): QueryBuilder {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('gp')
            ->from('Sports\Game\Place\Against', 'gp')
            ->join("gp.game", "g")
            ->join("g.poule", "p")
            ->join("p.round", "r")
            ->join("r.structureCell", "sc")
            ->join("sc.roundNumber", "rn")
            ->where('rn.competition = :competition')
            ->setParameter('competition', $competition);
        ;
        return $this->applyExtraFilters($query, $gameStates, $gameRoundNumber, $period);
    }

    /**
     * @param RoundNumber $roundNumber
     * @param list<GameState>|null $gameStates
     * @param int|null $gameRoundNumber
     * @return list<AgainstGamePlace>
     */
    public function getRoundNumberGames(RoundNumber $roundNumber, array $gameStates = null, int $gameRoundNumber = null): array
    {
        /** @var list<AgainstGamePlace> $games */
        $games = $this->getRoundNumberGamesQuery($roundNumber, $gameStates, $gameRoundNumber)->getQuery()->getResult();
        return $games;
    }

    /**
     * @param RoundNumber $roundNumber
     * @param list<GameState>|null $gameStates
     * @param int|null $gameRoundNumber
     * @return bool
     */
    public function hasRoundNumberGames(RoundNumber $roundNumber, array $gameStates = null, int $gameRoundNumber = null): bool
    {
        /** @var list<AgainstGamePlace> $games */
        $games = $this->getRoundNumberGamesQuery(
            $roundNumber,
            $gameStates,
            $gameRoundNumber
        )->setMaxResults(1)->getQuery()->getResult();
        return count($games) === 1;
    }

    /**
     * @param RoundNumber $roundNumber
     * @param list<GameState>|null $gameStates
     * @param int|null $gameRoundNumber
     * @return QueryBuilder
     * @throws \Exception
     */
    protected function getRoundNumberGamesQuery(RoundNumber $roundNumber, array $gameStates = null, int $gameRoundNumber = null): QueryBuilder
    {
        $query = $this->createQueryBuilder('g')
            ->join("g.poule", "p")
            ->join("p.round", "r")
            ->join("r.structureCell", "sc")
            ->where('sc.roundNumber = :roundNumber')
            ->setParameter('roundNumber', $roundNumber);
        ;
        return $this->applyExtraFilters($query, $gameStates, $gameRoundNumber);
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
        return  $query;
    }
}
