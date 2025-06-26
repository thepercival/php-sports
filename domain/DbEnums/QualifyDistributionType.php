<?php

declare(strict_types=1);

namespace Sports\DbEnums;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Sports\Qualify\QualifyDistribution;
use SportsHelpers\DbEnums\EnumDbType;

final class QualifyDistributionType extends EnumDbType
{
    #[\Override]
    public static function getNameHelper(): string
    {
        return 'enum_Distribution';
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): QualifyDistribution|null
    {
        if ($value === QualifyDistribution::HorizontalSnake->value) {
            return QualifyDistribution::HorizontalSnake;
        }
        if ($value === QualifyDistribution::Vertical->value) {
            return QualifyDistribution::Vertical;
        }
        return null;
    }

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'varchar(15)';
    }
}
