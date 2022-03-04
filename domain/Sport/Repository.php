<?php

declare(strict_types=1);

namespace Sports\Sport;

use Doctrine\ORM\EntityRepository;
use Sports\Sport as SportBase;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<SportBase>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<SportBase>
     */
    use BaseRepository;

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
