<?php

namespace Sports\Team\Role\Player;

use Sports\Team\Role\Player;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?Player
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
