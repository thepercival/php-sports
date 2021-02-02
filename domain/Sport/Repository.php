<?php

namespace Sports\Sport;

use League\Period\Period;
use Sports\Person as PersonBase;
use Sports\Sport as SportBase;
use Sports\Team;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?SportBase
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

    /**
     * @param bool|null $withCustomId
     * @return array|SportBase[]
     */
    public function findByExt(bool $withCustomId = null)
    {
        $qb = $this->createQueryBuilder('s');
        if( $withCustomId !== null ) {
            $operator = $withCustomId ? '>' : '=';
            $qb = $qb->andWhere('s.customId ' . $operator .' 0' );
        }
        return $qb->getQuery()->getResult();
    }
}
