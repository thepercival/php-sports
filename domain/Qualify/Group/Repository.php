<?php
declare(strict_types=1);

namespace Sports\Qualify\Group;

use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Qualify\Group as QualifyGroup;

/**
 * @template-extends EntityRepository<QualifyGroup>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<QualifyGroup>
     */
    use BaseRepository;
}
