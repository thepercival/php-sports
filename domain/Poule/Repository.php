<?php
declare(strict_types=1);

namespace Sports\Poule;

use Doctrine\ORM\EntityRepository;
use Sports\Poule as PouleBase;

/**
 * @template-extends EntityRepository<PouleBase>
 */
class Repository extends EntityRepository
{
    use \Sports\Repository;
}
