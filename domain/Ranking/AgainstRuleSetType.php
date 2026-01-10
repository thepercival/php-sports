<?php

declare(strict_types=1);

namespace Sports\Ranking;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\EnumDbType;

final class AgainstRuleSetType extends EnumDbType
{
    #[\Override]
    public static function getNameHelper(): string
    {
        return 'enum_AgainstRuleSet';
    }

    #[\Override]
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

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'varchar(10)';
    }
}
