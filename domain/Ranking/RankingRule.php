<?php

declare(strict_types=1);

namespace Sports\Ranking;

enum RankingRule: int
{
    case MostPoints = 1;
    case FewestGames = 2;
    case BestUnitDifference = 3;
    case MostUnitsScored = 4;
    case BestAmongEachOther = 5;
    case BestSubUnitDifference = 6;
    case MostSubUnitsScored = 7;
}
