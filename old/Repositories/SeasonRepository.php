<?php

declare(strict_types=1);

namespace old\Repositories;

use Doctrine\ORM\EntityRepository;
use League\Period\Period;
use Sports\Season;

/**
 * @template-extends EntityRepository<Season>
 */
final class SeasonRepository extends EntityRepository
{

    public function findOneByPeriod(Period $period): Season|null
    {
        $query = $this->createQueryBuilder('s')
            ->where('s.startDateTime < :end')
            ->andWhere('s.endDateTime > :start');

        $query = $query->setParameter('end', $period->endDate);
        $query = $query->setParameter('start', $period->startDate);

        /** @var list<Season> $seasons */
        $seasons = $query->getQuery()->getResult();
        $season = reset($seasons);
        return $season !== false ? $season : null;
    }
}
