<?php

declare(strict_types=1);

namespace Sports\Qualify;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Sports\Planning\EditMode;
use SportsHelpers\EnumDbType;

class DistributionType extends EnumDbType
{
    // const NAME = 'enum_Distribution'; // modify to match your type name

    public static function getNameHelper(): string
    {
        return 'enum_Distribution';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === Distribution::HorizontalSnake->value) {
            return Distribution::HorizontalSnake;
        }
        if ($value === Distribution::Vertical->value) {
            return Distribution::Vertical;
        }
        return null;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return 'int';
    }
}
