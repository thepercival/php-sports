<?php
declare(strict_types=1);

namespace Sports\Competition\Referee;

use SportsHelpers\Repository as BaseRepository;
use Sports\Competition\Referee;
use Doctrine\ORM\EntityRepository;

/**
 * @template-extends EntityRepository<Referee>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Referee>
     */
    use BaseRepository;
}
