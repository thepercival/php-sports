<?php

declare(strict_types=1);

namespace Sports\Ranking;

enum PointsCalculation: int
{
    case AgainstGamePoints = 0;
    case Scores = 1;
    case Both = 2;
}
