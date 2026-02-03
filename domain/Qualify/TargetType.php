<?php

declare(strict_types=1);

namespace Sports\Qualify;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\DbEnums\EnumDbType;
use Sports\Qualify\Target as QualifyTarget;

final class TargetType extends EnumDbType
{
    #[\Override]
    public static function getNameHelper(): string
    {
        return 'enum_QualifyTarget';
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): QualifyTarget|null
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

    #[\Override]
    public function getSQLDeclaration($column, AbstractPlatform $platform): string
    {
        return 'varchar(1)';
    }
}
