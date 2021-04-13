<?php
declare(strict_types=1);

namespace Sports\Association;

use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use Sports\Association as AssociationBase;

/**
 * @template-extends EntityRepository<AssociationBase>
 * @template-implements SaveRemoveRepository<AssociationBase>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
    use BaseRepository;
}
