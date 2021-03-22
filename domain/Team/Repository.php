<?php
declare(strict_types=1);

namespace Sports\Team;

use Doctrine\ORM\EntityRepository;
use Sports\Team as TeamBase;

/**
 * @template-extends EntityRepository<TeamBase>
 */
class Repository extends EntityRepository
{
    use \Sports\Repository;
    /*public function find($id, $lockMode = null, $lockVersion = null): ?TeamBase
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }*/
}
