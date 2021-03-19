<?php
declare(strict_types=1);

namespace Sports\Season;

use League\Period\Period;
use Sports\Season as SeasonBase;

class Repository extends \Sports\Repository
{
    /*public function find($id, $lockMode = null, $lockVersion = null): ?SeasonBase
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }*/

    public function findOneByPeriod(Period $period): ?SeasonBase
    {
        $query = $this->createQueryBuilder('s')
            ->where('s.startDateTime < :end')
            ->andWhere('s.endDateTime > :start');

        $query = $query->setParameter('end', $period->getEndDate());
        $query = $query->setParameter('start', $period->getEndDate());
        $seasons = $query->getQuery()->getResult();
        if (count($seasons) === 0) {
            return null;
        }
        return reset($seasons);
    }
}
