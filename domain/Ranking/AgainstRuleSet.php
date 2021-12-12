<?php

declare(strict_types=1);

namespace Sports\Ranking;

enum AgainstRuleSet: int
{
    case DiffFirst = 1;
    case AmongFirst = 2;
}
