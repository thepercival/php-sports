<?php
declare(strict_types=1);

namespace Sports\Game\Place\Together;

use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Game\Place\Together as TogetherGamePlace;

/**
 * @template-extends EntityRepository<TogetherGamePlace>
 * @template-implements SaveRemoveRepository<TogetherGamePlace>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
    use BaseRepository;
}
