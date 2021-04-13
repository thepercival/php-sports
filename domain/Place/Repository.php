<?php
declare(strict_types=1);

namespace Sports\Place;

use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Place as PlaceBase;

/**
 * @template-extends EntityRepository<PlaceBase>
 * @template-implements SaveRemoveRepository<PlaceBase>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
    use BaseRepository;
}
