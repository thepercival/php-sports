<?php
declare(strict_types=1);

namespace Sports\Person;

use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use League\Period\Period;
use Sports\Person;
use Sports\Person as PersonBase;
use Sports\Team;

/**
 * @template-extends EntityRepository<PersonBase>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<PersonBase>
     */
    use BaseRepository;

    /**
     * @param Period $period
     * @param Team|null $team
     * @param int|null $line
     * @param int|null $maxRows
     * @return list<PersonBase>
     */
    public function findByExt(Period $period, Team $team = null, int $line = null, int $maxRows = null)
    {
        $qb = $this->createQueryBuilder('p')
            ->distinct()
            ->join('Sports\Team\Player', 'pl', 'WITH', 'p = pl.person')
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
            $qb = $qb->andWhere('pl.line = :line');
            $qb = $qb->setParameter('line', $line);
        }
        if ($maxRows !== null) {
            $qb = $qb->setMaxResults($maxRows);
        }
        /** @var list<PersonBase> $persons */
        $persons = $qb->getQuery()->getResult();
        return $persons;
    }
}
