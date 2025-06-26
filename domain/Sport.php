<?php

declare(strict_types=1);

namespace Sports;

use InvalidArgumentException;
use Sports\Repositories\DBSport;
use Sports\Sport\Custom as CustomSport;
use SportsHelpers\Identifiable;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;

final class Sport extends DBSport
{
    public const int MIN_LENGTH_NAME = 3;
    public const int MAX_LENGTH_NAME = 30;
    public const int MIN_LENGTH_UNITNAME = 2;
    public const int MAX_LENGTH_UNITNAME = 20;

    public const int WARNING = 1;
    public const int SENDOFF = 2;

    private string $name;
    private int $customId = 0;

    public function __construct(
        string $name,
        private bool $team,
        AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport $sport
    ) {
        $this->setName($name);
        parent::__construct($sport);
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

    public function getCustomId(): int
    {
        return $this->customId;
    }

    public function setCustomId(int $id): void
    {
        $this->customId = $id;
    }

    public function hasNextDefaultScoreConfig(): bool
    {
        if (
            $this->customId === CustomSport::Badminton
            || $this->customId === CustomSport::Darts
            || $this->customId === CustomSport::Squash
            || $this->customId === CustomSport::TableTennis
            || $this->customId === CustomSport::Tennis
            || $this->customId === CustomSport::Volleyball
            || $this->customId === CustomSport::Padel
        ) {
            return true;
        }
        return false;
    }

    public function getDefaultWinPoints(): float
    {
        if ($this->customId === CustomSport::Rugby) {
            return 4;
        } elseif ($this->customId === CustomSport::Chess) {
            return 1;
        }
        return 3;
    }

    public function getDefaultDrawPoints(): float
    {
        if ($this->customId === CustomSport::Rugby) {
            return 2;
        }
        if ($this->customId === CustomSport::Chess) {
            return 0.5;
        }
        return 1;
    }

    public function getDefaultWinPointsExt(): float
    {
        if ($this->customId === CustomSport::Chess) {
            return 1;
        }
        return 2;
    }

    public function getDefaultDrawPointsExt(): float
    {
        if ($this->customId === CustomSport::Chess) {
            return 0.5;
        }
        return 1;
    }

    public function getDefaultLosePointsExt(): float
    {
        if ($this->customId === CustomSport::IceHockey) {
            return 1;
        }
        return 0;
    }
}
