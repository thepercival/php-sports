<?php

declare(strict_types=1);

namespace Sports\Association;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use Sports\Association as AssociationBase;

/**
 * @template-extends EntityRepository<AssociationBase>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<AssociationBase>
     */
    use BaseRepository;
}
