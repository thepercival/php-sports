<?php
declare(strict_types=1);

namespace Sports\Competitor\Team;

use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Competitor\Team as TeamCompetitor;

/**
 * @template-extends EntityRepository<TeamCompetitor>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<TeamCompetitor>
     */
    use BaseRepository;
}