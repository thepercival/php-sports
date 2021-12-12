<?php

declare(strict_types=1);

namespace Sports\Qualify;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\EnumDbType;
use Sports\Qualify\Target as QualifyTarget;

class TargetType extends EnumDbType
{
    public static function getNameHelper(): string
    {
        return 'enum_QualifyTarget';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === QualifyTarget::Winners->value) {
            return QualifyTarget::Winners;
        }
        if ($value === QualifyTarget::Losers->value) {
            return QualifyTarget::Losers;
        }
        if ($value === QualifyTarget::Dropouts->value) {
            return QualifyTarget::Dropouts;
        }
        return null;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return 'varchar(1)';
    }
}
