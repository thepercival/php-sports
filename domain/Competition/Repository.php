<?php
declare(strict_types=1);

namespace Sports\Competition;

use DateTimeImmutable;
use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use League\Period\Period;
use Sports\Competition;
use Sports\League;
use Sports\Season;
use Sports\Sport;

/**
 * @template-extends EntityRepository<Competition>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Competition>
     */
    use BaseRepository;

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

    public function findOneExt(League $league, Season $season): Competition|null
    {
        $query = $this->createQueryBuilder('c')
            ->where('c.season = :season')
            ->andWhere('c.league = :league');
        $query = $query->setParameter('season', $season);
        $query = $query->setParameter('league', $league);
        /** @var list<Competition> $results */
        $results = $query->getQuery()->getResult();
        $result = reset($results);
        return $result !== false ? $result : null;
    }

    public function findOneByLeagueAndDate(League $league, DateTimeImmutable $date): Competition|null
    {
        $query = $this->createQueryBuilder('c')
            ->join("c.season", "s")
            ->where('s.startDateTime <= :date')
            ->andWhere('s.endDateTime >= :date')
            ->andWhere('c.league = :league');

        $query = $query->setParameter('date', $date);
        $query = $query->setParameter('league', $league);
        /** @var list<Competition> $results */
        $results = $query->getQuery()->getResult();
        $result = reset($results);
        return $result !== false ? $result : null;
    }

    /**
     * @param DateTimeImmutable $date
     * @return list<Competition>
     */
    public function findByDate(DateTimeImmutable $date): array
    {
        $query = $this->createQueryBuilder('c')
            ->join("c.season", "s")
            ->where('s.startDateTime <= :date')
            ->andWhere('s.endDateTime >= :date');

        $query = $query->setParameter('date', $date);
        /** @var list<Competition> $results */
        $results = $query->getQuery()->getResult();
        return $results;
    }

    /**
     * @param Sport|null $sport
     * @param Period|null $period
     * @return list<Competition>
     */
    public function findExt(Sport $sport = null, Period $period = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->distinct()
            ->join('Sports\Competition\Sport', 'cs', 'WITH', 'c = cs.competition')
            ->join('cs.sport', 's')
            ->join('c.season', 'season')
        ;

        if ($sport !== null) {
            $qb = $qb->andWhere('cs.sport = :sport');
            $qb = $qb->setParameter('sport', $sport);
        }
        if ($period !== null) {
            $qb = $qb->andWhere('season.startDateTime < :periodEnd');
            $qb = $qb->andWhere('season.endDateTime > :periodStart');
            $qb = $qb->setParameter('periodEnd', $period->getEndDate());
            $qb = $qb->setParameter('periodStart', $period->getStartDate());
        }
        /** @var list<Competition> $results */
        $results = $qb->getQuery()->getResult();
        return $results;
    }

    /*public function findNrWithoutPlanning(): int
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->select('count(c.id)')
            ->distinct()
            ->from('Sports\Round\Number', 'rn')
            ->join("rn.competition", "c")
            ->where('rn.hasPlanning = false');

        // echo $queryBuilder->getQuery()->getSQL();
        // @var int $result
        $result = $queryBuilder->getQuery()->getSingleScalarResult();
        return $result;
    }*/
}
