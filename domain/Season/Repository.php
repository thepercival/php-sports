<?php

declare(strict_types=1);

namespace Sports\Season;

use Doctrine\ORM\EntityRepository;
use League\Period\Period;
use Sports\Season as SeasonBase;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<SeasonBase>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<SeasonBase>
     */
    use BaseRepository;

    public function findOneByPeriod(Period $period): SeasonBase|null
    {
        $query = $this->createQueryBuilder('s')
            ->where('s.startDateTime < :end')
            ->andWhere('s.endDateTime > :start');

        $query = $query->setParameter('end', $period->getEndDate());
        $query = $query->setParameter('start', $period->getEndDate());

        $seasons = $query->getQuery()->getResult();
        $season = reset($seasons);
        return $season !== false ? $season : null;
    }
}
