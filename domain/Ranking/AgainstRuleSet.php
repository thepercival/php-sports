<?php

declare(strict_types=1);

namespace Sports\Ranking;

enum AgainstRuleSet: string
{
    case DiffFirst = 'diffFirst';
    case AmongFirst = 'amongFirst';
}
