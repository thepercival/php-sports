<?php
declare(strict_types=1);

namespace Sports\Sport;

use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use SportsHelpers\Repository as BaseRepository;
use Sports\Sport as SportBase;
use Doctrine\ORM\EntityRepository;

/**
 * @template-extends EntityRepository<SportBase>
 * @template-implements SaveRemoveRepository<SportBase>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
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
            $qb = $qb->andWhere('s.customId ' . $operator .' 0');
        }
        /** @var list<SportBase> $result */
        $result = $qb->getQuery()->getResult();
        return $result;
    }
}
