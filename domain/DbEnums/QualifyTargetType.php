<?php

declare(strict_types=1);

namespace Sports\DbEnums;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Sports\Qualify\QualifyTarget as QualifyTarget;
use SportsHelpers\DbEnums\EnumDbType;

final class QualifyTargetType extends EnumDbType
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
