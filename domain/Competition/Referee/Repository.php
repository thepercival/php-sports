<?php

namespace Sports\Competition\Referee;

use Sports\Competition\Referee;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?Referee
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
