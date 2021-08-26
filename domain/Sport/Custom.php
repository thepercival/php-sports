<?php
declare(strict_types=1);

namespace Sports\Sport;

abstract class Custom
{
    const Min = 1;
    const Badminton = 1;
    const Basketball = 2;
    const Darts = 3;
    const ESports = 4;
    const Hockey = 5;
    const Korfball = 6;
    const Chess = 7;
    const Squash = 8;
    const TableTennis = 9;
    const Tennis = 10;
    const Football = 11;
    const Volleyball = 12;
    const Baseball = 13;
    const IceHockey = 14;
    const Shuffleboard = 15;
    const Jass = 16;
    const Padel = 17;
    const Max = 17;

    const Football_Line_GoalKepeer = 1;
    const Football_Line_Defense = 2;
    const Football_Line_Midfield = 4;
    const Football_Line_Forward = 8;
    const Football_Line_All = 15;

    /**
     * @return list<int>
     */
    public static function get(): array
    {
        return [
            Custom::Badminton,
            Custom::Basketball,
            Custom::Darts,
            Custom::ESports,
            Custom::Hockey,
            Custom::Baseball,
            Custom::Korfball,
            Custom::Chess,
            Custom::Squash,
            Custom::TableTennis,
            Custom::Tennis,
            Custom::Football,
            Custom::Volleyball,
            Custom::IceHockey,
            Custom::Shuffleboard,
            Custom::Jass,
            Custom::Padel,
        ];
    }
}
