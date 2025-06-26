<?php

declare(strict_types=1);

namespace Sports\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\Competitor\Team as TeamCompetitor;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<TeamCompetitor>
 */
class TeamCompetitorRepository extends EntityRepository
{
    /**
     * @use BaseRepository<TeamCompetitor>
     */
    use BaseRepository;
}
