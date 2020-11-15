<?php

namespace Sports\Team\Player;

use League\Period\Period;
use Sports\Person;
use Sports\Team;
use Sports\Team\Player as PlayerBase;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?PlayerBase
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

    /**
     * @param Period $period
     * @param Team|null $team
     * @param int|null $line
     * @param int|null $maxRows
     * @return array|PlayerBase[]
     */
    public function findByExt(Period $period, Team $team = null, int $line = null, int $maxRows = null)
    {
        $qb = $this->createQueryBuilder('pl')
            ->join('pl.person', 'p')
            ->where('pl.startDateTime <= :seasonEnd')
            ->andWhere('pl.endDateTime >= :seasonStart')
        ;

        $qb = $qb->setParameter('seasonEnd', $period->getEndDate() );
        $qb = $qb->setParameter('seasonStart', $period->getStartDate() );
        if( $team !== null ) {
            $qb = $qb->andWhere('pl.team = :team' );
            $qb = $qb->setParameter('team', $team );
        }
        if( $line !== null ) {
            $qb = $qb->andWhere('BIT_AND(pl.line, :lines) = pl.line');
            $qb = $qb->setParameter('lines', $line );
        }
        if( $maxRows !== null ) {
            $qb = $qb->setMaxResults($maxRows );
        }
        return $qb->getQuery()->getResult();
    }
}
