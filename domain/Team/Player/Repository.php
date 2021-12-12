<?php

declare(strict_types=1);

namespace Sports\Team\Player;

use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use League\Period\Period;
use Sports\Team;
use Sports\Team\Player as PlayerBase;

/**
 * @template-extends EntityRepository<PlayerBase>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<PlayerBase>
     */
    use BaseRepository;

    /**
     * @param Period $period
     * @param Team|null $team
     * @param int|null $line
     * @param int|null $maxRows
     * @return list<PlayerBase>
     */
    public function findByExt(Period $period, Team $team = null, int $line = null, int $maxRows = null): array
    {
        $qb = $this->createQueryBuilder('pl')
            ->join('pl.person', 'p')
            ->where('pl.startDateTime <= :seasonEnd')
            ->andWhere('pl.endDateTime >= :seasonStart')
        ;

        $qb = $qb->setParameter('seasonEnd', $period->getEndDate());
        $qb = $qb->setParameter('seasonStart', $period->getStartDate());
        if ($team !== null) {
            $qb = $qb->andWhere('pl.team = :team');
            $qb = $qb->setParameter('team', $team);
        }
        if ($line !== null) {
            $qb = $qb->andWhere('BIT_AND(pl.line, :lines) = pl.line');
            $qb = $qb->setParameter('lines', $line);
        }
        if ($maxRows !== null) {
            $qb = $qb->setMaxResults($maxRows);
        }
        /** @var list<PlayerBase> $result */
        $result = $qb->getQuery()->getResult();
        return $result;
    }
}
