<?php

declare(strict_types=1);

namespace Sports\DbEnums;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Sports\Sport\FootballLine;
use SportsHelpers\DbEnums\EnumDbType;

final class FootballLineType extends EnumDbType
{
    #[\Override]
    public static function getNameHelper(): string
    {
        return 'enum_FootballLine';
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): FootballLine|null
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

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'int';
    }
}
