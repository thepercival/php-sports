<?php

declare(strict_types=1);

namespace Sports\DbEnums;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Sports\Game\GameState;
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
    public function convertToPHPValue($value, AbstractPlatform $platform):GameState|null
    {
        if ($value === GameState::Created->value) {
            return GameState::Created;
        }
        if ($value === GameState::InProgress->value) {
            return GameState::InProgress;
        }
        if ($value === GameState::Finished->value) {
            return GameState::Finished;
        }
        if ($value === GameState::Canceled->value) {
            return GameState::Canceled;
        }
        return null;
    }

    /**
     * @psalm-suppress MethodSignatureMismatch
     */
    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'varchar(10)';
    }
}
