<?php

namespace Sports\League;

use Sports\League as LeagueBase;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?LeagueBase
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
