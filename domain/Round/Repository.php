<?php
declare(strict_types=1);

namespace Sports\Round;

use Doctrine\ORM\EntityRepository;
use Sports\Round as RoundBase;

/**
 * @template-extends EntityRepository<RoundBase>
 */
class Repository extends EntityRepository
{
    use \Sports\Repository;
}
