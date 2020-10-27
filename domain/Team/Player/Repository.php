<?php

namespace Sports\Team\Player;

use Sports\Team\Player;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?Player
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
