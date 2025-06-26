<?php

declare(strict_types=1);

namespace Sports\Ranking;

use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;

enum PointsCalculation: string
{
    case AgainstGamePoints = 'againstGamePoints';
    case Scores = 'scores';
    case Both = 'both';

    public static function getDefault(AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport $sport): self {
        if( $sport instanceof AgainstOneVsOne ||
            $sport instanceof AgainstOneVsTwo ||
            $sport instanceof AgainstTwoVsTwo) {
            return self::AgainstGamePoints;
        }
        return self::Scores;
    }
}
