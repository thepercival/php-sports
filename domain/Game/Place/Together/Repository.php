<?php
declare(strict_types=1);

namespace Sports\Game\Place\Together;

use Doctrine\ORM\EntityRepository;
use Sports\Game\Place\Together as TogetherGamePlace;

/**
 * @template-extends EntityRepository<TogetherGamePlace>
 */
class Repository extends EntityRepository
{
    use \Sports\Repository;
}
