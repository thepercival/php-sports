<?php

declare(strict_types=1);

namespace Sports\Category;

use Doctrine\ORM\EntityRepository;
use Sports\Category;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<Category>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Category>
     */
    use BaseRepository;
}
