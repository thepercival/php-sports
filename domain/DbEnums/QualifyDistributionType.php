<?php

declare(strict_types=1);

namespace Sports\DbEnums;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Sports\Qualify\Distribution;
use SportsHelpers\DbEnums\EnumDbType;

final class QualifyDistributionType extends EnumDbType
{
    // const NAME = 'enum_Distribution'; // modify to match your type name

    #[\Override]
    public static function getNameHelper(): string
    {
        return 'enum_Distribution';
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): Distribution|null
    {
        if ($value === Distribution::HorizontalSnake->value) {
            return Distribution::HorizontalSnake;
        }
        if ($value === Distribution::Vertical->value) {
            return Distribution::Vertical;
        }
        return null;
    }

    #[\Override]
    public function getSQLDeclaration($column, AbstractPlatform $platform): string
    {
        return 'varchar(15)';
    }
}
