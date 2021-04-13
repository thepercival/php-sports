<?php
declare(strict_types=1);

namespace Sports\Game\Place\Against;

use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Game\Place\Against as AgainstGamePlace;

/**
 * @template-extends EntityRepository<AgainstGamePlace>
 * @template-implements SaveRemoveRepository<AgainstGamePlace>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
    use BaseRepository;
}
