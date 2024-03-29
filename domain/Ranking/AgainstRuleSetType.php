<?php

declare(strict_types=1);

namespace Sports\Ranking;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\EnumDbType;

class AgainstRuleSetType extends EnumDbType
{
    public static function getNameHelper(): string
    {
        return 'enum_AgainstRuleSet';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === AgainstRuleSet::DiffFirst->value) {
            return AgainstRuleSet::DiffFirst;
        }
        if ($value === AgainstRuleSet::AmongFirst->value) {
            return AgainstRuleSet::AmongFirst;
        }
        return null;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return 'varchar(10)';
    }
}
