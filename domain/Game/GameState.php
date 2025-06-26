<?php

declare(strict_types=1);

namespace Sports\Game;

enum GameState: string
{
    case Created = 'created';
    case InProgress = 'inProgress';
    case Finished = 'finished';
    case Canceled = 'canceled';
}
