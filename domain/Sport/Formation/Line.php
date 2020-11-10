<?php

namespace Sports\Sport\Formation;

use Sports\Sport\Formation;

class Line
{
    CONST GOALKEEPER = 1;
    CONST DEFENSE = 2;
    CONST MIDFIELD = 4;
    CONST FORWARD = 8;
    CONST FOOTBALL_ALL = 15;

    /**
     * @var int
     */
    protected $id;
    protected int $number;
    protected int $nrOfPlayers;
    protected Formation$formation;

    public function __construct(Formation $formation, int $number, int $nrOfPlayers)
    {
        $this->setFormation($formation);
        $this->number = $number;
        $this->nrOfPlayers = $nrOfPlayers;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    public function getFormation(): Formation
    {
        return $this->formation;
    }

    /**
     * @param Formation $formation
     */
    protected function setFormation(Formation $formation)
    {
        if (!$formation->getLines()->contains($this)) {
            $formation->getLines()->add($this) ;
        }
        $this->formation = $formation;
    }

    public function getNumber(): int
    {
        return $this->number;
    }


    public function getNrOfPlayers()
    {
        return $this->nrOfPlayers;
    }
}
