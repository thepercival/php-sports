<?php

declare(strict_types=1);

namespace Sports\Formation;

use SportsHelpers\Identifiable;
use Sports\Formation;

class Line extends Identifiable
{
    public function __construct(
        protected Formation $formation,
        protected  int $number,
        protected  int $nrOfPersons
    ) {
        if (!$formation->getLines()->contains($this)) {
            $formation->getLines()->add($this) ;
        }
    }

    public function getFormation(): Formation
    {
        return $this->formation;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getNrOfPersons(): int
    {
        return $this->nrOfPersons;
    }

    public function equals(Line $formationLine): bool {
        return $this->getNumber() === $formationLine->getNumber()
            && $this->getNrOfPersons() === $formationLine->getNrOfPersons();
    }
}
