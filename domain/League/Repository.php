<?php
declare(strict_types=1);

namespace Sports\League;

use SportsHelpers\Repository as BaseRepository;
use Sports\League as LeagueBase;
use Doctrine\ORM\EntityRepository;

/**
 * @template-extends EntityRepository<LeagueBase>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<LeagueBase>
     */
    use BaseRepository;
}
