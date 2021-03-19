<?php
declare(strict_types=1);

namespace Sports\Sport;

use Sports\Sport as SportBase;

class Repository extends \Sports\Repository
{
    /*public function find($id, $lockMode = null, $lockVersion = null): ?SportBase
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }*/

    /**
     * @param bool|null $withCustomId
     * @return list<SportBase>
     */
    public function findByExt(bool $withCustomId = null)
    {
        $qb = $this->createQueryBuilder('s');
        if ($withCustomId !== null) {
            $operator = $withCustomId ? '>' : '=';
            $qb = $qb->andWhere('s.customId ' . $operator .' 0');
        }
        return $qb->getQuery()->getResult();
    }
}
