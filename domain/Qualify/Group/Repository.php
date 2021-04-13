<?php
declare(strict_types=1);

namespace Sports\Qualify\Group;

use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Qualify\Group as QualifyGroup;

/**
 * @template-extends EntityRepository<QualifyGroup>
 * @template-implements SaveRemoveRepository<QualifyGroup>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
    use BaseRepository;
}
