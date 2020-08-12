<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 13:17
 */

namespace Sports\Competitor\Team;

use Sports\Competitor\Team as TeamCompetitor;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?TeamCompetitor
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}