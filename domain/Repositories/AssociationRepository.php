<?php

declare(strict_types=1);

namespace Sports\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\Association as AssociationBase;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<AssociationBase>
 */
class AssociationRepository extends EntityRepository
{
    /**
     * @use BaseRepository<AssociationBase>
     */
    use BaseRepository;
}
