<?php

declare(strict_types=1);

namespace Sports\DbEnums;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Sports\Planning\EditMode;
use SportsHelpers\DbEnums\EnumDbType;

final class EditModeType extends EnumDbType
{
    #[\Override]
    public static function getNameHelper(): string
    {
        return 'enum_EditMode';
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): EditMode|null
    {
        if ($value === EditMode::Auto->value) {
            return EditMode::Auto;
        }
        if ($value === EditMode::Manual->value) {
            return EditMode::Manual;
        }
        return null;
    }

    /**
     * @psalm-suppress MethodSignatureMismatch
     */
    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'varchar(6)';
    }
}
