<?php
declare(strict_types=1);

namespace Sports\Competitor\Team;

use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Competitor\Team as TeamCompetitor;

/**
 * @template-extends EntityRepository<TeamCompetitor>
 * @template-implements SaveRemoveRepository<TeamCompetitor>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
    use BaseRepository;
}