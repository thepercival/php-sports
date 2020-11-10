<?php

namespace Sports\Team;

use Sports\Sport\Formation\Line as FormationLine;
use League\Period\Period;
use Sports\Person;
use Sports\Team;

class Player extends Role
{
    /**
     * @var int|null
     */
    protected $shirtNumber;
    protected int $line;

    public function __construct(Team $team, Person $person, Period $period, int $line)
    {
        parent::__construct($team, $person, $period);
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

    public function getLineLetter(): string {
        $line = $this->getLine();
        if( $line === FormationLine::GOALKEEPER ) {
            return "K";
        } elseif( $line === FormationLine::DEFENSE ) {
            return "V";
        } elseif( $line === FormationLine::MIDFIELD ) {
            return "M";
        } elseif( $line === FormationLine::FORWARD ) {
            return "A";
        }
        return "?";
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
