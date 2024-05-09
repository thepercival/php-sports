<?php

declare(strict_types=1);

namespace Sports\Game;

enum Order: string
{
    case ByPoule = 'byPoule';
    case ByBatch = 'byBatch';
    case ByDate = 'byDate';
}