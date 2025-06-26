<?php

declare(strict_types=1);

namespace Sports\Repositories\Structure;

use Doctrine\ORM\EntityRepository;
use Sports\Category;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<Category>
 */
class CategoryRepository extends EntityRepository
{
    /**
     * @use BaseRepository<Category>
     */
    use BaseRepository;
}
