<?php

declare(strict_types=1);

namespace Sports\Poule;

use Doctrine\Common\Collections\Collection;
use Exception;
use Sports\Place;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Rule\Horizontal\Multiple as HorizontalMultipleQualifyRule;
use Sports\Qualify\Rule\Horizontal\Single as HorizontalSingleQualifyRule;
use Sports\Qualify\Rule\Vertical\Multiple as VerticalMultipleQualifyRule;
use Sports\Qualify\Rule\Vertical\Single as VerticalSingleQualifyRule;
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
    protected HorizontalMultipleQualifyRule | HorizontalSingleQualifyRule |
                VerticalMultipleQualifyRule | VerticalSingleQualifyRule | null $qualifyRuleNew = null;

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

    public function getAbsoluteNumber(): int
    {
        if ($this->getQualifyTarget() !== QualifyTarget::Losers) {
            return $this->number;
        }
        $nrOfPlaceNubers = $this->round->getHorizontalPoules(QualifyTarget::Winners)->count();
        return $nrOfPlaceNubers - ($this->number - 1);
    }

    public function setQualifyRuleNew(HorizontalMultipleQualifyRule|HorizontalSingleQualifyRule|VerticalMultipleQualifyRule|VerticalSingleQualifyRule|null $qualifyRule): void
    {
        $this->qualifyRuleNew = $qualifyRule;
    }


    public function getQualifyRuleNew(): HorizontalMultipleQualifyRule|HorizontalSingleQualifyRule|VerticalMultipleQualifyRule|VerticalSingleQualifyRule|null
    {
        return $this->qualifyRuleNew;
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
