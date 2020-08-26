<?php

namespace Sports\Team\Role;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Person;
use Sports\Team;
use Sports\Team\Role;

class Player extends Role
{
    /**
     * @var int|null
     */
    protected $shirtNumber;
    protected int $line;

    public function __construct(Team $team, Person $person, DateTimeImmutable $startDateTime, DateTimeImmutable $endDateTime, int $line)
    {
        parent::__construct($team, $person, $startDateTime,$endDateTime);
        $this->setLine($line);
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

    public function getLine(): int
    {
        return $this->line;
    }

    public function setLine(int $line)
    {
        $this->line = $line;
    }

    public function getShirtNumber(): ?int
    {
        return $this->shirtNumber;
    }

    public function setShirtNumber(int $shirtNumber = null)
    {
        $this->shirtNumber = $shirtNumber;
    }
}
