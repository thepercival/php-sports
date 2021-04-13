<?php
declare(strict_types=1);

namespace Sports\Competition\Referee;

use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use SportsHelpers\Repository as BaseRepository;
use Sports\Competition\Referee;
use Doctrine\ORM\EntityRepository;

/**
 * @template-extends EntityRepository<Referee>
 * @template-implements SaveRemoveRepository<Referee>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
    use BaseRepository;
}
