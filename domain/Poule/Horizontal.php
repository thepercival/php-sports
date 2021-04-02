<?php

namespace Sports\Poule;

use Exception;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Round;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Place;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;

/**
 * QualifyGroup.WINNERS
 *  [ A1 B1 C1 ]
 *  [ A2 B2 C2 ]
 *  [ A3 B3 C3 ]
 * QualifyGroup.LOSERS
 *  [ C3 B3 A3 ]
 *  [ C2 B2 A2 ]
 *  [ C1 B1 A1 ]
 *
 **/
class Horizontal
{
    protected QualifyGroup|null $qualifyGroup = null;
    // protected MultipleQualifyRule|null $multipleRule = null;

    /**
     * @param Round $round
     * @param int $number
     * @param non-empty-list<Place> $places
     */
    public function __construct(protected Round $round, protected int $number, protected array $places)
    {
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    public function getWinnersOrLosers(): int
    {
        $qualifyGroup = $this->getQualifyGroup();
        return $qualifyGroup !== null ? $qualifyGroup->getWinnersOrLosers() : QualifyGroup::DROPOUTS;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getPlaceNumber(): int
    {
        if ($this->getWinnersOrLosers() !== QualifyGroup::LOSERS) {
            return $this->number;
        }
        $qualifyGroup = $this->getQualifyGroup();
        if ($qualifyGroup === null) {
            throw new Exception('kwalificatiegroep kan niet gevonden worden', E_ERROR);
        }
        $nrOfPlaceNumbers = count($qualifyGroup->getRound()->getHorizontalPoules2(QualifyGroup::WINNERS));
        return $nrOfPlaceNumbers - ($this->number - 1);
    }

    public function getQualifyGroup(): QualifyGroup|null
    {
        return $this->qualifyGroup;
    }

    public function setQualifyGroup(QualifyGroup|null $qualifyGroup): void
    {
        if ($this->qualifyGroup !== null) {
            $this->qualifyGroup->getHorizontalPoules()->removeElement($this);
        }
        $this->qualifyGroup = $qualifyGroup;
        if ($this->qualifyGroup !== null) {
            $this->qualifyGroup->getHorizontalPoules()->add($this);
        }
    }

    /*public function getMultipleQualifyRule(): MultipleQualifyRule|null
    {
        return $this->multipleRule;
    }

    public function setMultipleQualifyRule(MultipleQualifyRule|null $multipleRule = null): void
    {
        $this->multipleRule = $multipleRule;
    }*/

    /**
     * @return list<Place>
     */
    public function getPlaces2(): array
    {
        return $this->places;
    }

    public function getFirstPlace(): Place
    {
        return $this->places[0];
    }

    public function hasPlace(Place $place): bool
    {
        return array_search($place, $this->getPlaces2(), true) !== false;
    }

    // next(): Poule {
    //     const poules = this.getRound().getPoules();
    //     return poules[this.getNumber()];
    // }

    public function isBorderPoule(): bool
    {
        $qualifyGroup = $this->getQualifyGroup();
        if ($qualifyGroup === null || !$qualifyGroup->isBorderGroup()) {
            return false;
        }
        $horPoules = $qualifyGroup->getHorizontalPoules2();
        return end($horPoules) === $this;
    }

    public function getNrOfQualifiers(): int
    {
        $qualifyGroup = $this->getQualifyGroup();
        if ($qualifyGroup === null) {
            return 0;
        }
        if (!$this->isBorderPoule()) {
            return count($this->getPlaces2());
        }
        return count($this->getPlaces2()) - ($qualifyGroup->getNrOfToPlacesTooMuch());
    }
}
