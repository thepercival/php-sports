<?php
declare(strict_types=1);

namespace Sports\Place;

use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Place as PlaceBase;

/**
 * @template-extends EntityRepository<PlaceBase>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<PlaceBase>
     */
    use BaseRepository;
}
