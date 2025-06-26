<?php

declare(strict_types=1);

namespace Sports\Repositories\Structure;

use Doctrine\ORM\EntityRepository;
use Sports\Round as RoundBase;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<RoundBase>
 */
class RoundRepository extends EntityRepository
{
    /**
     * @use BaseRepository<RoundBase>
     */
    use BaseRepository;
}
