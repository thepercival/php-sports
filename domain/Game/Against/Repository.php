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
use Sports\Game\State;
use Sports\Game\State as GameState;
use Sports\Game\Together as TogetherGame;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\Against\Side;
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
            ->andWhere('g.startDateTime < :end')
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
        Period $period = null,
        int $maxResults = null
    ): array
    {
        $qb = $this->getCompetitionGamesQuery($competition);
        $qb = $this->applyExtraFilters($qb, $gameStates, $gameRoundNumber, $period);
        $qb = $qb->orderBy('g.startDateTime', 'ASC');
        if ($maxResults !== null) {
            $qb->setMaxResults($maxResults);
        }
        /** @var list<AgainstGame> $games */
        $games = $qb->getQuery()->getResult();
        return $games;
    }

    /**
     * @param Competition $competition
     * @param list<GameState>|null $gameStates
     * @param int|null $gameRoundNumber
     * @param Period|null $period
     * @return list<int>
     */
    public function getCompetitionGameRoundNumbers(
        Competition $competition,
        array $gameStates = null,
        Period $period = null
    ): array
    {
        $qb = $this->getCompetitionGamesQuery($competition);
        $qb = $this->applyExtraFilters($qb, $gameStates, null, $period);
        $qb = $qb->orderBy('g.startDateTime', 'ASC');
        /** @var list<AgainstGame> $games */
        $games = $qb->getQuery()->getResult();
        $gameRoundNumbers = array_map(function (AgainstGame $game): int {
            return $game->getGameRoundNumber();
        }, $games);
        return array_values(array_unique($gameRoundNumbers, SORT_NUMERIC));
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

    protected function getState(Competition $competition, int $gameRoundNr): State
    {
        $againstGames = $this->getCompetitionGames($competition, null, $gameRoundNr);
        if (count($againstGames) === 0) {
            return State::Created;
        }
        $created = array_filter($againstGames, function (AgainstGame $againstGame): bool {
            return $againstGame->getState() === State::Created;
        });
        $finished = array_filter($againstGames, function (AgainstGame $againstGame): bool {
            return $againstGame->getState() === State::Finished;
        });
        if (count($created) > 0 && count($finished) > 0) {
            return State::InProgress;
        }
        if (count($created) === 0 && count($finished) > 0) {
            $canceled = array_filter($againstGames, function (AgainstGame $againstGame): bool {
                return $againstGame->getState() === State::Canceled;
            });
            if( !$this->allCanceledInFinished(array_values($canceled),array_values($finished)) ) {
                return State::InProgress;
            }
            return State::Finished;
        }
        return State::Created;
    }

    /**
     * @param list<AgainstGame> $canceled
     * @param list<AgainstGame> $finished
     * @return bool
     */
    protected function allCanceledInFinished(array $canceled, array $finished): bool {
        foreach( $canceled as $canceledGame) {
            if( !$this->canceledGameInFinished($canceledGame, $finished) ) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param AgainstGame $canceledGame
     * @param list<AgainstGame> $finished
     * @return bool
     */
    protected function canceledGameInFinished(AgainstGame $canceledGame, array $finished): bool {
        $canceledHomeGamePlaces = $canceledGame->getSidePlaces(Side::Home);
        $canceledHomeGamePlace = array_shift($canceledHomeGamePlaces);
        $canceledAwayGamePlaces = $canceledGame->getSidePlaces(Side::Away);
        $canceledAwayGamePlace = array_shift($canceledAwayGamePlaces);
        if ($canceledHomeGamePlace === null || $canceledAwayGamePlace === null) {
            return false;
        }
        $canceledHomePlace = $canceledHomeGamePlace->getPlace();
        $canceledAwayPlace = $canceledAwayGamePlace->getPlace();

        foreach( $finished as $finishedGame) {
            $finishedHomeGamePlaces = $finishedGame->getSidePlaces(Side::Home);
            $finishedHomeGamePlace = array_shift($finishedHomeGamePlaces);
            $finishedAwayGamePlaces = $finishedGame->getSidePlaces(Side::Away);
            $finishedAwayGamePlace = array_shift($finishedAwayGamePlaces);
            if ($finishedHomeGamePlace === null || $finishedAwayGamePlace === null) {
                return false;
            }
            $finishedHomePlace = $finishedHomeGamePlace->getPlace();
            $finishedAwayPlace = $finishedAwayGamePlace->getPlace();
            if( $finishedHomePlace === $canceledHomePlace && $finishedAwayPlace === $canceledAwayPlace ) {
                return true;
            }
        }
        return false;
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
            ->setParameter('competition', $competition);;
        return $this->applyExtraFilters($query, $gameStates, $gameRoundNumber, $period);
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
        $qb = $this->getCompetitionGamesQuery($competition);
        $qb = $this->applyExtraFilters($qb, $gameStates, $gameRoundNumber, $period);
        /** @var list<AgainstGame> $games */
        $games = $qb->setMaxResults(1)->getQuery()->getResult();
        return count($games) === 1;
    }


    /**
     * @param RoundNumber $roundNumber
     * @param list<GameState>|null $gameStates
     * @param int|null $gameRoundNumber
     * @return list<AgainstGamePlace>
     */
    public function getRoundNumberGames(
        RoundNumber $roundNumber,
        array $gameStates = null,
        int $gameRoundNumber = null
    ): array {
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
            ->setParameter('roundNumber', $roundNumber);;
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
                ->andWhere('g.startDateTime < :end')
                ->andWhere('g.startDateTime >= :start')
                ->setParameter('end', $period->getEndDate())
                ->setParameter('start', $period->getStartDate());
        }
        return $query;
    }
}
