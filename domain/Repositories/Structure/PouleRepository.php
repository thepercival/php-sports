<?php

declare(strict_types=1);

namespace Sports\Repositories\Structure;

use Doctrine\ORM\EntityRepository;
use Sports\Poule as PouleBase;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<PouleBase>
 */
class PouleRepository extends EntityRepository
{
    /**
     * @use BaseRepository<PouleBase>
     */
    use BaseRepository;
}
