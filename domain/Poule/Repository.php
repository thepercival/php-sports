<?php
declare(strict_types=1);

namespace Sports\Poule;

use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Poule as PouleBase;

/**
 * @template-extends EntityRepository<PouleBase>
 * @template-implements SaveRemoveRepository<PouleBase>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
    use BaseRepository;
}
