<?php

declare(strict_types=1);

namespace Sports\Repositories\Games;

use Doctrine\ORM\EntityRepository;
use Sports\Game\Place\Against as AgainstGamePlace;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<AgainstGamePlace>
 */
class AgainstGamePlaceRepository extends EntityRepository
{
    /**
     * @use BaseRepository<AgainstGamePlace>
     */
    use BaseRepository;
}
