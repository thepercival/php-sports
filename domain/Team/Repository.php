<?php
declare(strict_types=1);

namespace Sports\Team;

use SportsHelpers\Repository as BaseRepository;
use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Team as TeamBase;

/**
 * @template-extends EntityRepository<TeamBase>
 * @template-implements SaveRemoveRepository<TeamBase>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
    use BaseRepository;
}
