<?php

declare(strict_types=1);

namespace Sports\Game;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\EnumDbType;

final class StateType extends EnumDbType
{
    // const NAME = 'enum_GameMode'; // modify to match your type name

    #[\Override]
    public static function getNameHelper(): string
    {
        return 'enum_GameState';
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === State::Created->value) {
            return State::Created;
        }
        if ($value === State::InProgress->value) {
            return State::InProgress;
        }
        if ($value === State::Finished->value) {
            return State::Finished;
        }
        if ($value === State::Canceled->value) {
            return State::Canceled;
        }
        return null;
    }

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'varchar(10)';
    }
}
