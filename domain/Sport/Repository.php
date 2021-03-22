<?php
declare(strict_types=1);

namespace Sports\Sport;

use Sports\Sport as SportBase;
use Doctrine\ORM\EntityRepository;

/**
 * @template-extends EntityRepository<SportBase>
 */
class Repository extends EntityRepository
{
    use \Sports\Repository;

    /**
     * @param bool|null $withCustomId
     * @return list<SportBase>
     */
    public function findByExt(bool $withCustomId = null): array
    {
        $qb = $this->createQueryBuilder('s');
        if ($withCustomId !== null) {
            $operator = $withCustomId ? '>' : '=';
            $qb = $qb->andWhere('s.customId ' . $operator .' 0');
        }
        /** @var list<SportBase> $result */
        $result = $qb->getQuery()->getResult();
        return $result;
    }
}
