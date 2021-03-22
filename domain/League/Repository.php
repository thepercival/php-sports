<?php
declare(strict_types=1);

namespace Sports\League;

use Sports\League as LeagueBase;
use Doctrine\ORM\EntityRepository;

/**
 * @template-extends EntityRepository<LeagueBase>
 */
class Repository extends EntityRepository
{
    use \Sports\Repository;
}
