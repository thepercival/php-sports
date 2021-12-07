<?php
declare(strict_types=1);

namespace Sports\Ranking;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\EnumDbType;

class PointsCalculationType extends EnumDbType
{
    static public function getNameHelper(): string
    {
        return 'enum_PointsCalculation';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if( $value === PointsCalculation::AgainstGamePoints->value ) {
            return PointsCalculation::AgainstGamePoints;
        }
        if( $value === PointsCalculation::Scores->value ) {
            return PointsCalculation::Scores;
        }
        if( $value === PointsCalculation::Both->value ) {
            return PointsCalculation::Both;
        }
        return null;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform){
        return 'int';
    }
}