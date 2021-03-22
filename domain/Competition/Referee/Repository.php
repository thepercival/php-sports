<?php

namespace Sports\Competition\Referee;

use Sports\Competition\Referee;
use Doctrine\ORM\EntityRepository;

/**
 * @template-extends EntityRepository<Referee>
 */
class Repository extends EntityRepository
{
    use \Sports\Repository;

    /*public function find($id, $lockMode = null, $lockVersion = null): ?Referee
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }*/
}
