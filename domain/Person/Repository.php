<?php

namespace Sports\Person;

use League\Period\Period;
use Sports\Person;
use Sports\Person as PersonBase;
use Sports\Team;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?PersonBase
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

    /**
     * @param Period $period
     * @param Team|null $team
     * @param int|null $line
     * @param int|null $maxRows
     * @return array|PersonBase[]
     */
    public function findByExt(Period $period, Team $team = null, int $line = null, int $maxRows = null)
    {
        $qb = $this->createQueryBuilder('p')
            ->distinct()
            ->join('Sports\Team\Player', 'pl', 'WITH', 'p = pl.person')
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
            $qb = $qb->andWhere('pl.line = :line' );
            $qb = $qb->setParameter('line', $line );
        }
        if( $maxRows !== null ) {
            $qb = $qb->setMaxResults($maxRows );
        }
        return $qb->getQuery()->getResult();
    }
}
