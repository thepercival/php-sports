<?php

declare(strict_types=1);

namespace Sports\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\League as LeagueBase;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<LeagueBase>
 */
class LeagueRepository extends EntityRepository
{
    /**
     * @use BaseRepository<LeagueBase>
     */
    use BaseRepository;
}
