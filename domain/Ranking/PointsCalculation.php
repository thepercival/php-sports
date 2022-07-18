<?php

declare(strict_types=1);

namespace Sports\Ranking;

use SportsHelpers\Sport\Variant\Against;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;

enum PointsCalculation: int
{
    case AgainstGamePoints = 0;
    case Scores = 1;
    case Both = 2;

    public static function getDefault(Single|Against|AllInOneGame $sportVariant): self {
        if( $sportVariant instanceof Against) {
            return self::AgainstGamePoints;
        }
        return self::Scores;
    }
}
