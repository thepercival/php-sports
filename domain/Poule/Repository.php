<?php
declare(strict_types=1);

namespace Sports\Poule;

use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Poule as PouleBase;

/**
 * @template-extends EntityRepository<PouleBase>
 */
class Repository extends EntityRepository
{
    use BaseRepository;
}
