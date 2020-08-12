<?php

namespace Sports\Field;

use Sports\Field;

/**
 * Class Repository
 * @package Voetbal
 */
class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?Field
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
