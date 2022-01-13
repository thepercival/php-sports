<?php

declare(strict_types=1);

namespace Sports;

use InvalidArgumentException;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\GameMode;
use SportsHelpers\Identifiable;
use SportsHelpers\Sport\PersistVariant as SportPersistVariant;

class Sport extends Identifiable
{
    public const MIN_LENGTH_NAME = 3;
    public const MAX_LENGTH_NAME = 30;
    public const MIN_LENGTH_UNITNAME = 2;
    public const MAX_LENGTH_UNITNAME = 20;

    public const WARNING = 1;
    public const SENDOFF = 2;

    private string $name;
    private int $customId = 0;

    public function __construct(
        string $name,
        private bool $team,
        private GameMode $defaultGameMode,
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

    public function getDefaultGameMode(): GameMode
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

    public function createAgainstPersistVariant(int $nrOfH2H, int|null $nrOfSidePlaces = null): SportPersistVariant
    {
        return new SportPersistVariant(
            GameMode::Against,
            $nrOfSidePlaces !== null ? $nrOfSidePlaces : $this->getDefaultNrOfSidePlaces(),
            $nrOfSidePlaces !== null ? $nrOfSidePlaces : $this->getDefaultNrOfSidePlaces(),
            0,
            $nrOfH2H,
            0
        );
    }

    public function createTogetherPersistVariant(
        GameMode $gameMode,
        int $nrOfGamePlaces,
        int $nrOfGamesPerPlace
    ): SportPersistVariant {
        return new SportPersistVariant(
            $gameMode,
            0,
            0,
            $nrOfGamePlaces,
            0,
            $nrOfGamesPerPlace
        );
    }

    public function getDefaultGameModeNative(): int
    {
        return $this->defaultGameMode->value;
    }

    public function setDefaultGameModeNative(int $defaultGameMode): void
    {
        $this->defaultGameMode = GameMode::from($defaultGameMode);
    }
}
