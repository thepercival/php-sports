<?php

declare(strict_types=1);

namespace Sports\Game\Against;

use Sports\Game\Repository as GameRepository;
use Sports\Competitor;
use Sports\Game\Against as AgainstGame;
use League\Period\Period;

class Repository extends GameRepository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?AgainstGame
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

    public function findOneByExt(Competitor $homeCompetitor, Competitor $awayCompetitor, Period $period): ?AgainstGame
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
            );
        $query = $query->setParameter('home', AgainstGame::HOME);
        $query = $query->setParameter('homecompetitor', $homeCompetitor);
        $query = $query->setParameter('away', AgainstGame::AWAY);
        $query = $query->setParameter('awaycompetitor', $awayCompetitor);
        $query = $this->applyExtraFilters( $query, null, null, $period );
        $games = $query->getQuery()->getResult();
        if (count($games) === 0) {
            return null;
        }
        return reset($games);
    }
}