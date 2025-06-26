<?php

declare(strict_types=1);

namespace Sports\Repositories;

use Doctrine\ORM\EntityRepository;
use League\Period\Period;
use Sports\Season;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<Season>
 */
class SeasonRepository extends EntityRepository
{
    /**
     * @use BaseRepository<Season>
     */
    use BaseRepository;

    public function findOneByPeriod(Period $period): Season|null
    {
        $query = $this->createQueryBuilder('s')
            ->where('s.startDateTime < :end')
            ->andWhere('s.endDateTime > :start');

        $query = $query->setParameter('end', $period->getEndDate());
        $query = $query->setParameter('start', $period->getEndDate());

        /** @var list<Season> $seasons */
        $seasons = $query->getQuery()->getResult();
        $season = reset($seasons);
        return $season !== false ? $season : null;
    }
}
