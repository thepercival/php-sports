<?php

declare(strict_types=1);

namespace Sports\Competitor;

use InvalidArgumentException;
use Sports\Competition;
use Sports\Competitor as CompetitorInterface;
use Sports\Team as TeamBase;

class Team extends StartLocation implements CompetitorInterface
{
    public const int MAX_LENGTH_INFO = 200;

    protected int|string|null $id = null;
    protected bool $present = false;
    protected string|null $publicInfo = null;
    protected string|null $privateInfo = null;

    public function __construct(
        protected Competition $competition,
        StartLocation $startLoc,
        protected TeamBase $team
    ) {
        parent::__construct($startLoc->getCategoryNr(), $startLoc->getPouleNr(), $startLoc->getPlaceNr());
        if (!$competition->getTeamCompetitors()->contains($this)) {
            $competition->getTeamCompetitors()->add($this);
        }
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function setId(int|string|null $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->team->getName();
    }

    public function getTeam(): TeamBase
    {
        return $this->team;
    }

    public function getCompetition(): Competition
    {
        return $this->competition;
    }

    public function getPresent(): bool
    {
        return $this->present;
    }

    public function setPresent(bool $present): void
    {
        $this->present = $present;
    }

    public function getPublicInfo(): ?string
    {
        return $this->publicInfo;
    }

    public function setPublicInfo(string $info = null): void
    {
        if ($info !== null && strlen($info) === 0) {
            $info = null;
        }
        if ($info !== null && strlen($info) > self::MAX_LENGTH_INFO) {
            throw new InvalidArgumentException(
                'de extra-info mag maximaal ' . self::MAX_LENGTH_INFO . ' karakters bevatten', E_ERROR
            );
        }
        $this->publicInfo = $info;
    }

    public function getPrivateInfo(): ?string
    {
        return $this->privateInfo;
    }

    public function setPrivateInfo(string $info = null): void
    {
        if ($info !== null && strlen($info) === 0) {
            $info = null;
        }
        if ($info !== null && strlen($info) > self::MAX_LENGTH_INFO) {
            throw new InvalidArgumentException(
                'de extra-info mag maximaal ' . self::MAX_LENGTH_INFO . ' karakters bevatten', E_ERROR
            );
        }
        $this->privateInfo = $info;
    }
}
