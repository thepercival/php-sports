<?php

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
    private Team $team;
    /**
     * @var Person|null
     */
    private $person;

    const MIN_LENGTH_NAME = 2;
    const MAX_LENGTH_NAME = 30;
    const MAX_LENGTH_ABBREVIATION = 3;
    const MAX_LENGTH_IMAGEURL = 150;

    public function __construct(Team $team, Person $person, Period $period)
    {
        $this->setTeam($team);
        $this->setPerson($person);
        $this->setStartDateTime($period->getStartDate());
        $this->setEndDateTime($period->getEndDate());
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function setTeam(Team $team): void
    {
        $this->team = $team;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(Person $person): void
    {
        if ($this->person !== null
            && $this->person->getPlayers()->contains($this)) {
            $this->person->getPlayers()->removeElement($this) ;
        }
        if (!$person->getPlayers()->contains($this)) {
            $person->getPlayers()->add($this) ;
        }
        $this->person = $person;
    }
    
    public function getStartDateTime(): DateTimeImmutable
    {
        return $this->startDateTime;
    }

    public function setStartDateTime(DateTimeImmutable $startDateTime): void
    {
        $this->startDateTime = $startDateTime;
    }

    public function getEndDateTime(): DateTimeImmutable
    {
        return $this->endDateTime;
    }

    public function setEndDateTime(DateTimeImmutable $endDateTime): void
    {
        $this->endDateTime = $endDateTime;
    }

    public function getPeriod(): Period {
        return new Period( $this->getStartDateTime(), $this->getEndDateTime() );
    }

    public function setPeriod(Period $period): void
    {
        $this->setStartDateTime( $period->getStartDate() );
        $this->setEndDateTime( $period->getEndDate() );
    }
}
