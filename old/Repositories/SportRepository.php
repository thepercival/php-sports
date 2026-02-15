<?php

declare(strict_types=1);

namespace old\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\Sport as SportBase;

/**
 * @template-extends EntityRepository<SportBase>
 */
final class SportRepository extends EntityRepository
{
    /**
     * @param bool|null $withCustomId
     * @return list<SportBase>
     */
    public function findByExt(bool $withCustomId = null): array
    {
        $qb = $this->createQueryBuilder('s');
        if ($withCustomId !== null) {
            $operator = $withCustomId ? '>' : '=';
            $qb = $qb->andWhere('s.customId ' . $operator . ' 0');
        }
        /** @var list<SportBase> $results */
        $results = $qb->getQuery()->getResult();
        return $results;
    }
}
