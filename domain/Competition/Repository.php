<?php

namespace Sports\Competition;

use League\Period\Period;
use Sports\Competition;
use Sports\League;
use Sports\Season;
use Sports\Sport;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?Competition
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

    public function customPersist(Competition $competition): void
    {
        foreach ($competition->getReferees() as $referee) {
            $this->_em->persist($referee);
        }

        foreach ($competition->getSports() as $competitionSport) {
            $this->_em->persist($competitionSport);
            foreach ($competitionSport->getFields() as $field) {
                $this->_em->persist($field);
            }
        }

        $this->_em->persist($competition);
    }

    public function findOneExt(League $league, Season $season): ?Competition
    {
        $query = $this->createQueryBuilder('c')
            ->where('c.season = :season')
            ->andWhere('c.league = :league');
        $query = $query->setParameter('season', $season);
        $query = $query->setParameter('league', $league);
        $results = $query->getQuery()->getResult();
        $result = reset($results);
        return $result;
    }

    public function findOneByLeagueAndDate(League $league, \DateTimeImmutable $date)
    {
        $query = $this->createQueryBuilder('c')
            ->join("c.season", "s")
            ->where('s.startDateTime <= :date')
            ->andWhere('s.endDateTime >= :date')
            ->andWhere('c.league = :league');

        $query = $query->setParameter('date', $date);
        $query = $query->setParameter('league', $league);

        $results = $query->getQuery()->getResult();
        $result = reset($results);
        return $result;
    }

    public function findByDate(\DateTimeImmutable $date)
    {
        $query = $this->createQueryBuilder('c')
            ->join("c.season", "s")
            ->where('s.startDateTime <= :date')
            ->andWhere('s.endDateTime >= :date');

        $query = $query->setParameter('date', $date);
        return $query->getQuery()->getResult();
    }

    /**
     * @param Sport $sport
     * @param Period|null $period
     * @return array|Competition[]
     */
    public function findExt(Sport $sport = null, Period $period = null)
    {
        $qb = $this->createQueryBuilder('c')
            ->distinct()
            ->join('Sports\Sport\Config', 'sc', 'WITH', 'c = sc.competition')
            ->join('sc.sport', 's')
            ->join('c.season', 'season')
        ;

        if( $sport !== null ) {
            $qb = $qb->andWhere('sc.sport = :sport');
            $qb = $qb->setParameter('sport', $sport );
        }
        if( $period !== null ) {
            $qb = $qb->andWhere('season.startDateTime < :periodEnd' );
            $qb = $qb->andWhere('season.endDateTime > :periodStart' );
            $qb = $qb->setParameter('periodEnd', $period->getEndDate() );
            $qb = $qb->setParameter('periodStart', $period->getStartDate() );
        }
        return $qb->getQuery()->getResult();
    }

    public function findNrWithoutPlanning()
    {
        $queryBuilder = $this->getEM()->createQueryBuilder()
            ->select('count(c.id)')
            ->distinct()
            ->from('Sports\Round\Number', 'rn')
            ->join("rn.competition", "c")
            ->where('rn.hasPlanning = false');

        // echo $queryBuilder->getQuery()->getSQL();

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

}
