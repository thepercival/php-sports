<?php

declare(strict_types=1);

namespace Sports\Game\Together;

use Sports\Game\Repository as GameRepository;
use Sports\Game\Together as TogetherGame;

class Repository extends GameRepository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?TogetherGame
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
