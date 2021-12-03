<?php
declare(strict_types=1);

namespace Sports\Poule;

use Doctrine\Common\Collections\Collection;
use Exception;
use Sports\Place;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Round;

/**
 * QualifyGroup->WINNERS
 *  [ A1 B1 C1 ]
 *  [ A2 B2 C2 ]
 *  [ A3 B3 C3 ]
 * QualifyGroup->LOSERS
 *  [ C3 B3 A3 ]
 *  [ C2 B2 A2 ]
 *  [ C1 B1 A1 ]
 *
 **/
class Horizontal
{
    protected int $number;
    protected MultipleQualifyRule | SingleQualifyRule | null $qualifyRule = null;

    /**
     * @param Round $round
     * @param QualifyTarget $qualifyTarget
     * @param Horizontal|null $previous
     * @param Collection<int|string, Place> $places
     */
    public function __construct(
        protected Round $round,
        protected QualifyTarget $qualifyTarget,
        protected HorizontalPoule | null $previous,
        protected Collection $places
    ) {
        $round->getHorizontalPoules($qualifyTarget)->add($this);
        $this->number = $previous !== null ? $previous->getNumber() + 1 : 1;
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    public function getQualifyTarget(): QualifyTarget
    {
        return $this->qualifyTarget;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getPlaceNumber(): int
    {
        if ($this->getQualifyTarget() !== QualifyTarget::Losers) {
            return $this->number;
        }
        $nrOfPlaceNubers = $this->round->getHorizontalPoules(QualifyTarget::Winners)->count();
        return $nrOfPlaceNubers - ($this->number - 1);
    }

    public function setQualifyRule(MultipleQualifyRule | SingleQualifyRule | null $qualifyRule): void
    {
        $this->qualifyRule = $qualifyRule;
    }


    public function getQualifyRule(): SingleQualifyRule | MultipleQualifyRule | null
    {
        return $this->qualifyRule;
    }

    /**
     * @return Collection<int|string, Place>
     */
    public function getPlaces(): Collection
    {
        return $this->places;
    }

    public function getFirstPlace(): Place
    {
        $firstPlace = $this->places->first();
        if ($firstPlace === false) {
            throw new Exception("horizontalpoule should have firstPlace", E_ERROR);
        }
        return $firstPlace;
    }

    public function hasPlace(Place $place): bool
    {
        $places = $this->getPlaces()->filter(function (Place $placeIt) use ($place): bool {
            return $placeIt === $place;
        });
        return $places->count() > 0;
    }
}
