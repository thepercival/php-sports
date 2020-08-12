<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-3-17
 * Time: 20:18
 */

namespace Sports\Game;

use Doctrine\ORM\QueryBuilder;
use Sports\Round\Number as RoundNumber;
use Sports\Competitor;
use Sports\Competition;
use Sports\Game as GameBase;
use League\Period\Period;

class Repository extends \Sports\Repository
{
    public function getCompetitionGames(Competition $competition, $gameStates = null, int $batchNr = null)
    {
        return $this->getCompetitionGamesQuery($competition, $gameStates, $batchNr)->getQuery()->getResult();
    }

    public function hasCompetitionGames(Competition $competition, $gameStates = null, int $batchNr = null)
    {
        $games = $this->getCompetitionGamesQuery(
            $competition,
            $gameStates,
            $batchNr
        )->setMaxResults(1)->getQuery()->getResult();
        return count($games) === 1;
    }

    protected function getCompetitionGamesQuery(Competition $competition, $gameStates = null, int $batchNr = null): QueryBuilder
    {
        $query = $this->createQueryBuilder('g')
            ->join("g.poule", "p")
            ->join("p.round", "r")
            ->join("r.number", "rn")
            ->where('rn.competition = :competition')
            ->setParameter('competition', $competition);
        ;
        return $this->applyExtraFilters($query, $gameStates, $batchNr);
    }

    public function getRoundNumberGames(RoundNumber $roundNumber, $gameStates = null, int $batchNr = null)
    {
        return $this->getRoundNumberGamesQuery($roundNumber, $gameStates, $batchNr)->getQuery()->getResult();
    }

    public function hasRoundNumberGames(RoundNumber $roundNumber, $gameStates = null, int $batchNr = null)
    {
        $games = $this->getRoundNumberGamesQuery(
            $roundNumber,
            $gameStates,
            $batchNr
        )->setMaxResults(1)->getQuery()->getResult();
        return count($games) === 1;
    }

    protected function getRoundNumberGamesQuery(RoundNumber $roundNumber, $gameStates = null, int $batchNr = null): QueryBuilder
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

    protected function applyExtraFilters(QueryBuilder $query, int $gameStates = null, int $batchNr = null): QueryBuilder
    {
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
        return  $query;
    }

    public function findOneByExt(Competitor $homeCompetitor, Competitor $awayCompetitor, Period $period): ?GameBase
    {
        $exprHome = $this->getEM()->getExpressionBuilder();
        $exprAway = $this->getEM()->getExpressionBuilder();

        $query = $this->createQueryBuilder('g')
            ->where('g.startDateTime >= :start')
            ->andWhere('g.startDateTime <= :end')
            ->andWhere(
                $exprHome->exists(
                    $this->getEM()->createQueryBuilder()
                        ->select('gpphome.id')
                        ->from('Sports\Game\Place', 'gpphome')
                        ->join("gpphome.place", "pphome")
                        ->where('gpphome.game = g')
                        ->andWhere('gpphome.homeaway = :home')
                        ->andWhere('pphome.competitor = :homecompetitor')
                        ->getDQL()
                )
            )
            ->andWhere(
                $exprAway->exists(
                    $this->getEM()->createQueryBuilder()
                        ->select('gppaway.id')
                        ->from('Sports\Game\Place', 'gppaway')
                        ->join("gppaway.place", "ppaway")
                        ->where('gppaway.game = g')
                        ->andWhere('gppaway.homeaway = :away')
                        ->andWhere('ppaway.competitor = :awaycompetitor')
                        ->getDQL()
                )
            )
        ;
        $query = $query->setParameter('start', $period->getStartDate());
        $query = $query->setParameter('end', $period->getEndDate());
        $query = $query->setParameter('home', GameBase::HOME);
        $query = $query->setParameter('homecompetitor', $homeCompetitor);
        $query = $query->setParameter('away', GameBase::AWAY);
        $query = $query->setParameter('awaycompetitor', $awayCompetitor);
        $games = $query->getQuery()->getResult();
        if (count($games) === 0) {
            return null;
        }
        return reset($games);
    }

    /**
     * @param GameBase $game
     */
    public function customRemove(GameBase $game)
    {
        $game->getPoule()->getGames()->removeElement($game);
        return $this->remove($game);
    }
}
