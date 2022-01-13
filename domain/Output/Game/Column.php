<?php

declare(strict_types=1);

namespace Sports\Output\Game;

enum Column: int {
    case State = 1;
    case StartDateTime = 2;
    case GameRoundNumber = 4;
    case BatchNr = 8;
    case Poule = 16;
    case ScoreAndPlaces = 32;
    case Referee = 64;
    case Field = 128;
    case Sport = 256;
    case Points = 512;
    case ScoresLineupsAndEvents = 1024;

    public static function has(int $columns, self $column): bool
    {
        return ($columns & $column->value) === $column->value;
    }

    /**
     * @param list<self>|null $enums
     * @return int
     */
    public static function sum(array $enums = null): int
    {
        if ($enums === null) {
            $enums = self::cases();
        }
        return array_sum(array_map(fn (Column $enum) => $enum->value, $enums));
    }
}
