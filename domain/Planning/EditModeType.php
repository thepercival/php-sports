<?php

declare(strict_types=1);

namespace Sports\Planning;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\EnumDbType;

class EditModeType extends EnumDbType
{
    // const NAME = 'enum_GameMode'; // modify to match your type name

    public static function getNameHelper(): string
    {
        return 'enum_EditMode';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === EditMode::Auto->value) {
            return EditMode::Auto;
        }
        if ($value === EditMode::Manual->value) {
            return EditMode::Manual;
        }
        return null;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return 'varchar(6)';
    }
}
