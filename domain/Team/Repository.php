<?php

declare(strict_types=1);

namespace Sports\Team;

use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Team as TeamBase;

/**
 * @template-extends EntityRepository<TeamBase>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<TeamBase>
     */
    use BaseRepository;
}
