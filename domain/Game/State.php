<?php

declare(strict_types=1);

namespace Sports\Game;

enum State: int
{
    case Created = 1;
    case InProgress = 2;
    case Finished = 4;
    case Canceled = 8;
}
