<?php

namespace Sports\Team;

use League\Period\Period;
use Sports\Person;
use Sports\Team;
use Sports\Sport\Custom as SportCustom;

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
        if( $line === SportCustom::Football_Line_GoalKepeer ) {
            return "K";
        } elseif( $line === SportCustom::Football_Line_Defense ) {
            return "V";
        } elseif( $line === SportCustom::Football_Line_Midfield ) {
            return "M";
        } elseif( $line === SportCustom::Football_Line_Forward ) {
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
