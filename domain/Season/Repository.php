<?php

namespace Sports\Season;

use Sports\Season as SeasonBase;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?SeasonBase
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
