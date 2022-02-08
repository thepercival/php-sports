<?php

declare(strict_types=1);

namespace Sports\Sport;

enum FootballLine: int
{
    case GoalKeeper = 1;
    case Defense = 2;
    case Midfield = 4;
    case Forward = 8;

    public static function getFirstChar(self $line): string
    {
        if ($line === FootballLine::GoalKeeper) {
            return "K";
        } elseif ($line === FootballLine::Defense) {
            return "V";
        } elseif ($line === FootballLine::Midfield) {
            return "M";
        } elseif ($line === FootballLine::Forward) {
            return "A";
        }
        return "?";
    }

}
