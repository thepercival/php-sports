<?php
declare(strict_types=1);

namespace Sports\Planning\Config;

use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Planning\Config as PlanningConfig;

/**
 * @template-extends EntityRepository<PlanningConfig>
 * @template-implements SaveRemoveRepository<PlanningConfig>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
    use BaseRepository;
//    public function customSave()
//    {
//        doe transactie en sla op
//
//    $this->removeFrom($roundNumber->getNext());
//    }
}
