<?php
declare(strict_types=1);

namespace Sports\Round;

use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Round as RoundBase;

/**
 * @template-extends EntityRepository<RoundBase>
 * @template-implements SaveRemoveRepository<RoundBase>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
    use BaseRepository;
}
