<?php

namespace Sports\Poule;

use Exception;
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
    /**
     * @var Round
     */
    protected $round;
    /**
     * @var QualifyGroup
     */
    protected $qualifyGroup;
    /**
     * @var int
     */
    protected $number;
    /**
     * @var array<Place>
     */
    protected array $places = [];
    protected MultipleQualifyRule|null $multipleRule = null;

    public function __construct(Round $round, int $number)
    {
        $this->round = $round;
        $this->number = $number;
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    public function setRound(Round $round): void
    {
        $this->round = $round;
    }

    public function getWinnersOrLosers(): int
    {
        return $this->getQualifyGroup() !== null ? $this->getQualifyGroup()->getWinnersOrLosers() : QualifyGroup::DROPOUTS;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
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
        $nrOfPlaceNubers = count($qualifyGroup->getRound()->getHorizontalPoules(QualifyGroup::WINNERS));
        return $nrOfPlaceNubers - ($this->number - 1);
    }

    public function getQualifyGroup(): ?QualifyGroup
    {
        return $this->qualifyGroup;
    }

    public function setQualifyGroup(?QualifyGroup $qualifyGroup): void
    {

        // this is done in horizontalpouleservice
        // if( this.qualifyGroup != null ){ // remove from old round
        //     var index = this.qualifyGroup.getHorizontalPoules().indexOf(this);
        //     if (index > -1) {
        //         this.round.getHorizontalPoules().splice(index, 1);
        //     }
        // }
        $this->qualifyGroup = $qualifyGroup;
        if ($qualifyGroup !== null) {
            $horizontalPoules = &$this->qualifyGroup->getHorizontalPoules();
            $horizontalPoules[] = $this;
        }
    }

    public function getMultipleQualifyRule(): MultipleQualifyRule|null
    {
        return $this->multipleRule;
    }

    public function setMultipleQualifyRule(MultipleQualifyRule|null $multipleRule = null): void
    {
        foreach ($this->getPlaces() as $place) {
            $place->setToQualifyRule($this->getWinnersOrLosers(), $multipleRule);
        }
        $this->multipleRule = $multipleRule;
    }

    /**
     * @return array<Place>
     */
    public function &getPlaces(): array
    {
        return $this->places;
    }

    public function getFirstPlace(): Place
    {
        return $this->places[0];
    }

    public function hasPlace(Place $place): bool
    {
        return array_search($place, $this->getPlaces(), true) !== false;
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
        $horPoules = $qualifyGroup->getHorizontalPoules();
        return $horPoules[count($horPoules)-1] === $this;
    }

    public function getNrOfQualifiers(): int
    {
        $qualifyGroup = $this->getQualifyGroup();
        if ($qualifyGroup === null) {
            return 0;
        }
        if (!$this->isBorderPoule()) {
            return count($this->getPlaces());
        }
        return count($this->getPlaces()) - ($qualifyGroup->getNrOfToPlacesTooMuch());
    }
}
