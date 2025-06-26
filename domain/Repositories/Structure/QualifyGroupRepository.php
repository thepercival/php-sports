<?php

declare(strict_types=1);

namespace Sports\Repositories\Structure;

use Doctrine\ORM\EntityRepository;
use Sports\Qualify\Group as QualifyGroup;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<QualifyGroup>
 */
class QualifyGroupRepository extends EntityRepository
{
    /**
     * @use BaseRepository<QualifyGroup>
     */
    use BaseRepository;
}
