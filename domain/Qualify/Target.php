<?php
declare(strict_types=1);

namespace Sports\Qualify;

enum Target: string
{
    case Winners = 'W';
    case Dropouts = '';
    case Losers = 'L';

    public function getOpposing(): self {
        return $this === Target::Winners ? Target::Losers : Target::Winners;
    }
}