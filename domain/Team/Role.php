<?php

namespace Sports\Team;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Person;
use Sports\Team;
use SportsHelpers\Identifiable;

abstract class Role implements Identifiable
{
    /**
     * @var int|string
     */
    protected $id;
    private DateTimeImmutable $startDateTime;
    private DateTimeImmutable $endDateTime;
    private Team $team;
    private Person $person;

    const MIN_LENGTH_NAME = 2;
    const MAX_LENGTH_NAME = 30;
    const MAX_LENGTH_ABBREVIATION = 3;
    const MAX_LENGTH_IMAGEURL = 150;

    public function __construct(Team $team, Person $person, DateTimeImmutable $startDateTime, DateTimeImmutable $endDateTime)
    {
        $this->setTeam($team);
        $this->setPerson($person);
        $this->setStartDateTime($startDateTime);
        $this->setEndDateTime($endDateTime);
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|string $id
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    public function getTeam(): DateTimeImmutable
    {
        return $this->team;
    }

    public function setTeam(DateTimeImmutable $team)
    {
        $this->team = $team;
    }

    public function getPerson(): DateTimeImmutable
    {
        return $this->person;
    }

    public function setPerson(DateTimeImmutable $person)
    {
        $this->person = $person;
    }
    
    public function getStartDateTime(): DateTimeImmutable
    {
        return $this->startDateTime;
    }

    public function setStartDateTime(DateTimeImmutable $startDateTime)
    {
        $this->startDateTime = $startDateTime;
    }

    public function getEndDateTime(): DateTimeImmutable
    {
        return $this->endDateTime;
    }

    public function setEndDateTime(DateTimeImmutable $endDateTime)
    {
        $this->endDateTime = $endDateTime;
    }

    public function getPeriod(): Period {
        return new Period( $this->getStartDateTime(), $this->getEndDateTime() );
    }
}
