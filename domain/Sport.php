<?php

declare(strict_types=1);

namespace Sports;

use InvalidArgumentException;
use SportsHelpers\SportBase;

class Sport extends SportBase
{
    const MIN_LENGTH_NAME = 3;
    const MAX_LENGTH_NAME = 30;
    const MIN_LENGTH_UNITNAME = 2;
    const MAX_LENGTH_UNITNAME = 20;

    const WARNING = 1;
    const SENDOFF = 2;

    private string $name;
    private bool $team;
    private int $customId = 0;
    protected bool $againstEachOther;

    public function __construct(string $name, bool $team, int $nrOfGamePlaces, bool $againstEachOther)
    {
        parent::__construct($nrOfGamePlaces);
        $this->setName($name);
        $this->team = $team;
        $this->againstEachOther = $againstEachOther;
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected function setName(string $name): void
    {
        if (strlen($name) < static::MIN_LENGTH_NAME or strlen($name) > static::MAX_LENGTH_NAME) {
            throw new InvalidArgumentException("de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
        }
        $this->name = $name;
    }


    public function getTeam(): bool
    {
        return $this->team;
    }

    public function getCustomId(): ?int
    {
        return $this->customId;
    }

    public function setCustomId(int $id ): void
    {
        $this->customId = $id;
    }

    public function getAgainstEachOther(): bool
    {
        return $this->againstEachOther;
    }
}
