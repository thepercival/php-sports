<?php

namespace Sports\Competition\Field;

use Doctrine\ORM\EntityRepository;
use Sports\Competition\Field;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<Field>
 */
class Repository extends EntityRepository
{
    use BaseRepository;
}
