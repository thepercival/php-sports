<?php

namespace Sports\Person;

use Sports\Person as PersonBase;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?PersonBase
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
