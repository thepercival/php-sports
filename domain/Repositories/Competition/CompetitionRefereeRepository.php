<?php

declare(strict_types=1);

namespace Sports\Repositories\Competition;

use Doctrine\ORM\EntityRepository;
use Sports\Competition\Referee;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<Referee>
 */
class CompetitionRefereeRepository extends EntityRepository
{
    /**
     * @use BaseRepository<Referee>
     */
    use BaseRepository;
}
