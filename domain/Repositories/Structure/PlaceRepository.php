<?php

declare(strict_types=1);

namespace Sports\Repositories\Structure;

use Doctrine\ORM\EntityRepository;
use Sports\Place as PlaceBase;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<PlaceBase>
 */
class PlaceRepository extends EntityRepository
{
    /**
     * @use BaseRepository<PlaceBase>
     */
    use BaseRepository;
}
