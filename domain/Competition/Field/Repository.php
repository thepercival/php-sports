<?php

namespace Sports\Competition\Field;

use Doctrine\ORM\EntityRepository;
use Sports\Competition\Field;

/**
 * @template-extends EntityRepository<Field>
 */
class Repository extends EntityRepository
{
    use \Sports\Repository;
    /*public function find($id, $lockMode = null, $lockVersion = null): ?Field
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }*/
}
