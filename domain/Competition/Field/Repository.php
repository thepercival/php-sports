<?php

namespace Sports\Competition\Field;

use Sports\Competition\Field;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?Field
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
