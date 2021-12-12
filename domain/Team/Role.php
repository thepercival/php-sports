<?php

declare(strict_types=1);

namespace Sports\Team;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Person;
use Sports\Team;
use SportsHelpers\Identifiable;

abstract class Role extends Identifiable
{
    private DateTimeImmutable $startDateTime;
    private DateTimeImmutable $endDateTime;

    public const MIN_LENGTH_NAME = 2;
    public const MAX_LENGTH_NAME = 30;
    public const MAX_LENGTH_ABBREVIATION = 3;
    public const MAX_LENGTH_IMAGEURL = 150;

    public function __construct(protected Team $team, protected Person $person, Period $period)
    {
        $this->setStartDateTime($period->getStartDate());
        $this->setEndDateTime($period->getEndDate());
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function getPerson(): Person
    {
        return $this->person;
    }

    public function getStartDateTime(): DateTimeImmutable
    {
        return $this->startDateTime;
    }

    final public function setStartDateTime(DateTimeImmutable $startDateTime): void
    {
        $this->startDateTime = $startDateTime;
    }

    public function getEndDateTime(): DateTimeImmutable
    {
        return $this->endDateTime;
    }

    final public function setEndDateTime(DateTimeImmutable $endDateTime): void
    {
        $this->endDateTime = $endDateTime;
    }

    public function getPeriod(): Period
    {
        return new Period($this->getStartDateTime(), $this->getEndDateTime());
    }

    public function setPeriod(Period $period): void
    {
        $this->setStartDateTime($period->getStartDate());
        $this->setEndDateTime($period->getEndDate());
    }
}
