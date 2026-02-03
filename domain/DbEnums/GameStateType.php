<?php

declare(strict_types=1);

namespace Sports\DbEnums;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Sports\Game\State;
use SportsHelpers\DbEnums\EnumDbType;

final class GameStateType extends EnumDbType
{
    // const NAME = 'enum_GameMode'; // modify to match your type name

    #[\Override]
    public static function getNameHelper(): string
    {
        return 'enum_GameState';
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): State|null
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
    public function getSQLDeclaration($column, AbstractPlatform $platform): string
    {
        return 'varchar(10)';
    }
}
