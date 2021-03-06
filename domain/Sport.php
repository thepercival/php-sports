<?php
declare(strict_types=1);

namespace Sports;

use InvalidArgumentException;
use SportsHelpers\Identifiable;

class Sport extends Identifiable
{
    const MIN_LENGTH_NAME = 3;
    const MAX_LENGTH_NAME = 30;
    const MIN_LENGTH_UNITNAME = 2;
    const MAX_LENGTH_UNITNAME = 20;

    const WARNING = 1;
    const SENDOFF = 2;

    private string $name;
    private int $customId = 0;

    public function __construct(
        string $name,
        private bool $team,
        private int $defaultGameMode,
        private int $defaultNrOfSidePlaces,
    ) {
        $this->setName($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    final public function setName(string $name): void
    {
        if (strlen($name) < self::MIN_LENGTH_NAME or strlen($name) > self::MAX_LENGTH_NAME) {
            throw new InvalidArgumentException("de naam moet minimaal ".self::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".self::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
        }
        $this->name = $name;
    }


    public function getTeam(): bool
    {
        return $this->team;
    }

    public function getDefaultGameMode(): int
    {
        return $this->defaultGameMode;
    }

    public function getDefaultNrOfSidePlaces(): int
    {
        return $this->defaultNrOfSidePlaces;
    }

    public function getCustomId(): int
    {
        return $this->customId;
    }

    public function setCustomId(int $id): void
    {
        $this->customId = $id;
    }
}
