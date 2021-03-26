<?php
declare(strict_types=1);

namespace Sports\Team;

use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Team as TeamBase;

/**
 * @template-extends EntityRepository<TeamBase>
 */
class Repository extends EntityRepository
{
    use BaseRepository;
    /*public function find($id, $lockMode = null, $lockVersion = null): ?TeamBase
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }*/
}
