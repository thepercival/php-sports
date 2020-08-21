<?php

namespace Sports\Sport;

use Sports\Sport as SportBase;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?SportBase
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
