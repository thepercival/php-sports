<?php
declare(strict_types=1);

namespace Sports\Qualify;

enum Target: string
{
    case WINNERS = 'W';
    case DROPOUTS = '';
    case LOSERS = 'L';

    public function getOpposing(): self {
        return $this === Target::WINNERS ? Target::LOSERS : Target::WINNERS;
    }
}