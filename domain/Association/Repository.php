<?php

namespace Sports\Association;

use Sports\Association as AssociationBase;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?AssociationBase
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
