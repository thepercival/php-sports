<?php

declare(strict_types=1);

namespace Sports\Sport;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\DbEnums\EnumDbType;

class FootballLineType extends EnumDbType
{
    public static function getNameHelper(): string
    {
        return 'enum_FootballLine';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === FootballLine::GoalKeeper->value) {
            return FootballLine::GoalKeeper;
        }
        if ($value === FootballLine::Defense->value) {
            return FootballLine::Defense;
        }
        if ($value === FootballLine::Midfield->value) {
            return FootballLine::Midfield;
        }
        if ($value === FootballLine::Forward->value) {
            return FootballLine::Forward;
        }
        return null;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return 'int';
    }
}
