<?php

declare(strict_types=1);

namespace Sports\Sport;

abstract class Custom
{
    public const Min = 1;
    public const Badminton = 1;
    public const Basketball = 2;
    public const Darts = 3;
    public const ESports = 4;
    public const Hockey = 5;
    public const Korfball = 6;
    public const Chess = 7;
    public const Squash = 8;
    public const TableTennis = 9;
    public const Tennis = 10;
    public const Football = 11;
    public const Volleyball = 12;
    public const Baseball = 13;
    public const IceHockey = 14;
    public const Shuffleboard = 15;
    public const Jass = 16;
    public const Padel = 17;
    public const Rugby = 18;
    public const Max = 18;

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
            Custom::Rugby,
        ];
    }
}
