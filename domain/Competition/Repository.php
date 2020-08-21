<?php

namespace Sports\Competition;

use Sports\Competition;
use Sports\League;
use Sports\Season;

/**
 * Class Repository
 * @package Sports\Competition
 */
class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?Competition
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

    public function customPersist(Competition $competition)
    {
        foreach ($competition->getReferees() as $referee) {
            $this->_em->persist($referee);
        }

        foreach ($competition->getSportConfigs() as $sportConfig) {
            $this->_em->persist($sportConfig);
            foreach ($sportConfig->getFields() as $field) {
                $this->_em->persist($field);
            }
        }

        $this->_em->persist($competition);
    }

    public function findExt(League $league, Season $season): ?Competition
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

//        if ( $studentnummer !== null ){
//            $query = $query->andWhere('s.studentnummer = :studentnummer');
//        }

        $query = $query->setParameter('date', $date);
        $query = $query->setParameter('league', $league);


//        if ( $studentnummer !== null ){
//            $query = $query->setParameter('studentnummer', $studentnummer);
//        }
        $results = $query->getQuery()->getResult();
        $result = reset($results);
        return $result;
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
