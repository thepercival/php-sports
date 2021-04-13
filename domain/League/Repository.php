<?php
declare(strict_types=1);

namespace Sports\League;

use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use SportsHelpers\Repository as BaseRepository;
use Sports\League as LeagueBase;
use Doctrine\ORM\EntityRepository;

/**
 * @template-extends EntityRepository<LeagueBase>
 * @template-implements SaveRemoveRepository<LeagueBase>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
    use BaseRepository;
}
