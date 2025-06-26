<?php

declare(strict_types=1);

namespace Sports\Repositories\Competition;

use Doctrine\ORM\EntityRepository;
use Sports\Competition\Field;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<Field>
 */
class CompetitionFieldRepository extends EntityRepository
{
    /**
     * @use BaseRepository<Field>
     */
    use BaseRepository;
}
