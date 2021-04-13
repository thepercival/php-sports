<?php
declare(strict_types=1);

namespace Sports\Game\Together;

use Sports\Game\Repository as GameRepository;
use Sports\Game\Together as TogetherGame;

/**
 * @template-extends GameRepository<TogetherGame>
 */
class Repository extends GameRepository
{
}
