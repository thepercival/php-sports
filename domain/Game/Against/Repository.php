<?php

declare(strict_types=1);

namespace Sports\Game\Against;

use Sports\Game\Repository as GameRepository;
use Sports\Competitor;
use Sports\Game\Against as AgainstGame;
use League\Period\Period;
use SportsHelpers\Against\Side as AgainstSide;

/**
 * @template-extends GameRepository<AgainstGame>
 */
class Repository extends GameRepository
{
    public function findOneByExt(Competitor $homeCompetitor, Competitor $awayCompetitor, Period $period): ?AgainstGame
    {
        $exprHome = $this->_em->getExpressionBuilder();
        $exprAway = $this->_em->getExpressionBuilder();

        $query = $this->createQueryBuilder('g')
            ->where('g.startDateTime >= :start')
            ->andWhere('g.startDateTime <= :end')
            ->andWhere(
                $exprHome->exists(
                    $this->_em->createQueryBuilder()
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
                    $this->_em->createQueryBuilder()
                        ->select('gppaway.id')
                        ->from('Sports\Game\Place', 'gppaway')
                        ->join("gppaway.place", "ppaway")
                        ->where('gppaway.game = g')
                        ->andWhere('gppaway.side = :away')
                        ->andWhere('ppaway.competitor = :awaycompetitor')
                        ->getDQL()
                )
            );
        $query = $query->setParameter('home', AgainstSide::HOME);
        $query = $query->setParameter('homecompetitor', $homeCompetitor);
        $query = $query->setParameter('away', AgainstSide::AWAY);
        $query = $query->setParameter('awaycompetitor', $awayCompetitor);
        $query = $this->applyExtraFilters($query, null, null, $period);
        /** @var list<AgainstGame> $games */
        $games = $query->getQuery()->getResult();
        $firstGame = reset($games);
        return $firstGame !== false ? $firstGame : null;
    }
}
