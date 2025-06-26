<?php

declare(strict_types=1);

namespace Sports\Repositories;

// use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Team as TeamBase;

/**
 * @template-extends EntityRepository<TeamBase>
 */
class TeamRepository extends EntityRepository
{
//    /**
//     * @use RepositoryTrait<TeamBase>
//     */
//    use RepositoryTrait;
}
