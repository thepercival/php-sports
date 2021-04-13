<?php
declare(strict_types=1);

namespace Sports\Competition\Field;

use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Competition\Field;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<Field>
 * @template-implements SaveRemoveRepository<Field>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
    use BaseRepository;
}
