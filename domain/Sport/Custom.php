<?php

declare(strict_types=1);

namespace Sports\Sport;

abstract class Custom
{
    public const int Min = 1;
    public const int Badminton = 1;
    public const int Basketball = 2;
    public const int Darts = 3;
    public const int ESports = 4;
    public const int Hockey = 5;
    public const int Korfball = 6;
    public const int Chess = 7;
    public const int Squash = 8;
    public const int TableTennis = 9;
    public const int Tennis = 10;
    public const int Football = 11;
    public const int Volleyball = 12;
    public const int Baseball = 13;
    public const int IceHockey = 14;
    public const int Shuffleboard = 15;
    public const int Jass = 16;
    public const int Padel = 17;
    public const int Rugby = 18;
    public const int Max = 18;

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
