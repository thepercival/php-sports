<?php

declare(strict_types=1);

namespace Sports\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\Planning\PlanningConfig as PlanningConfig;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<PlanningConfig>
 */
class PlanningConfigRepository extends EntityRepository
{
    /**
     * @use BaseRepository<PlanningConfig>
     */
    use BaseRepository;
//    public function customSave()
//    {
//        doe transactie en sla op
//
//    $this->removeFrom($roundNumber->getNext());
//    }
}
