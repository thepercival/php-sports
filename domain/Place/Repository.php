<?php
declare(strict_types=1);

namespace Sports\Place;

use Doctrine\ORM\EntityRepository;
use Sports\Place as PlaceBase;

/**
 * @template-extends EntityRepository<PlaceBase>
 */
class Repository extends EntityRepository
{
    use \Sports\Repository;
}
