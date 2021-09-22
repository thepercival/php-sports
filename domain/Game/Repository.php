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

    /**
     * @param Competition $competition
     * @param int|null $gameStates
     * @param int|null $batchNr
     * @param Period|null $period
     * @return list<T>
     */
    public function getCompetitionGames(
        Competition $competition,
        int $gameStates = null,
        int $batchNr = null,
        Period $period = null
    ): array {
        $qb = $this->getCompetitionGamesQuery($competition, $gameStates, $batchNr, $period);
        $qb = $qb->orderBy('g.startDateTime', 'ASC');
        /** @var list<T> $games */
        $games = $qb->getQuery()->getResult();
        return $games;
    }

    public function hasCompetitionGames(
        Competition $competition,
        int $gameStates = null,
        int $batchNr = null,
        Period $period = null
    ): bool {
        /** @var list<T> $games */
        $games = $this->getCompetitionGamesQuery(
            $competition,
            $gameStates,
            $batchNr,
            $period
        )->setMaxResults(1)->getQuery()->getResult();
        return count($games) === 1;
    }

    public function getNrOfCompetitionGamePlaces(
        Competition $competition,
        int $gameStates = null,
        int $batchNr = null,
        Period $period = null
    ): int {
        /** @var list<GamePlace> $gamePlaces */
        $gamePlaces = $this->getCompetitionGamePlacessQuery(
            $competition,
            $gameStates,
            $batchNr,
            $period
        )->getQuery()->getResult();
        return count($gamePlaces);
    }

    protected function getCompetitionGamesQuery(
        Competition $competition,
        int $gameStates = null,
        int $batchNr = null,
        Period $period = null
    ): QueryBuilder {
        $query = $this->createQueryBuilder('g')
            ->join("g.poule", "p")
            ->join("p.round", "r")
            ->join("r.number", "rn")
            ->where('rn.competition = :competition')
            ->setParameter('competition', $competition);
        ;
        return $this->applyExtraFilters($query, $gameStates, $batchNr, $period);
    }

    protected function getCompetitionGamePlacessQuery(
        Competition $competition,
        int $gameStates = null,
        int $batchNr = null,
        Period $period = null
    ): QueryBuilder {
        $query = $this->_em->createQueryBuilder()
            ->select('gp')
            ->from('Sports\Game\Place', 'gp')
            ->join("gp.game", "g")
            ->join("g.poule", "p")
            ->join("p.round", "r")
            ->join("r.number", "rn")
            ->where('rn.competition = :competition')
            ->setParameter('competition', $competition);
        ;
        return $this->applyExtraFilters($query, $gameStates, $batchNr, $period);
    }

    /**
     * @param RoundNumber $roundNumber
     * @param int|null $gameStates
     * @param int|null $batchNr
     * @return list<T>
     */
    public function getRoundNumberGames(RoundNumber $roundNumber, int $gameStates = null, int $batchNr = null): array
    {
        /** @var list<T> $games */
        $games = $this->getRoundNumberGamesQuery($roundNumber, $gameStates, $batchNr)->getQuery()->getResult();
        return $games;
    }

    public function hasRoundNumberGames(RoundNumber $roundNumber, int $gameStates = null, int $batchNr = null): bool
    {
        /** @var list<T> $games */
        $games = $this->getRoundNumberGamesQuery(
            $roundNumber,
            $gameStates,
            $batchNr
        )->setMaxResults(1)->getQuery()->getResult();
        return count($games) === 1;
    }

    protected function getRoundNumberGamesQuery(RoundNumber $roundNumber, int $gameStates = null, int $batchNr = null): QueryBuilder
    {
        $query = $this->createQueryBuilder('g')
            ->join("g.poule", "p")
            ->join("p.round", "r")
            ->join("r.number", "rn")
            ->where('rn.roundNumber = :roundNumber')
            ->setParameter('roundNumber', $roundNumber);
        ;
        return $this->applyExtraFilters($query, $gameStates, $batchNr);
    }

    protected function applyExtraFilters(
        QueryBuilder $query,
        int $gameStates = null,
        int $batchNr = null,
        Period $period = null
    ): QueryBuilder {
        if ($gameStates !== null) {
            // $query = $query->andWhere('g.state & :gamestates = g.state');
            $query = $query
                ->andWhere('BIT_AND(g.state, :gamestates) > 0')
                ->setParameter('gamestates', $gameStates);
        }
        if ($batchNr !== null) {
            $query = $query
                ->andWhere('g.batchNr = :batchNr')
                ->setParameter('batchNr', $batchNr);
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
}
