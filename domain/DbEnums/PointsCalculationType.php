<?php

declare(strict_types=1);

namespace Sports\DbEnums;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Sports\Ranking\PointsCalculation;
use SportsHelpers\DbEnums\EnumDbType;

final class PointsCalculationType extends EnumDbType
{
    #[\Override]
    public static function getNameHelper(): string
    {
        return 'enum_PointsCalculation';
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): PointsCalculation|null
    {
        if ($value === PointsCalculation::AgainstGamePoints->value) {
            return PointsCalculation::AgainstGamePoints;
        }
        if ($value === PointsCalculation::Scores->value) {
            return PointsCalculation::Scores;
        }
        if ($value === PointsCalculation::Both->value) {
            return PointsCalculation::Both;
        }
        return null;
    }

    /**
     * @psalm-suppress MethodSignatureMismatch
     */
    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'varchar(17)';
    }
}
