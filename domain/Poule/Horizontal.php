<?php

namespace Sports\Poule;

use Sports\Round;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Place;
use Sports\Qualify\Rule\Multiple as QualifyRuleMultiple;

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
     * @var array | Place[]
     */
    protected $places = [];
    /**
     * @var QualifyRuleMultiple
     */
    protected $multipleRule;

    public function __construct(Round $round, int $number)
    {
        $this->places = [];
        $this->round = $round;
        $this->number = $number;
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    public function setRound(Round $round)
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

    public function setNumber(int $number)
    {
        $this->number = $number;
    }

    public function getPlaceNumber(): int
    {
        if ($this->getWinnersOrLosers() !== QualifyGroup::LOSERS) {
            return $this->number;
        }
        $nrOfPlaceNubers = count($this->getQualifyGroup()->getRound()->getHorizontalPoules(QualifyGroup::WINNERS));
        return $nrOfPlaceNubers - ($this->number - 1);
    }

    public function getQualifyGroup(): ?QualifyGroup
    {
        return $this->qualifyGroup;
    }

    public function setQualifyGroup(?QualifyGroup $qualifyGroup)
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

    public function getQualifyRuleMultiple(): ?QualifyRuleMultiple
    {
        return $this->multipleRule;
    }

    public function setQualifyRuleMultiple(QualifyRuleMultiple $multipleRule = null)
    {
        foreach ($this->getPlaces() as $place) {
            $place->setToQualifyRule($this->getWinnersOrLosers(), $multipleRule);
        }
        $this->multipleRule = $multipleRule;
    }

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
        if ($this->getQualifyGroup() === null || !$this->getQualifyGroup()->isBorderGroup()) {
            return false;
        }
        $horPoules = $this->getQualifyGroup()->getHorizontalPoules();
        return $horPoules[count($horPoules)-1] === $this;
    }

    public function getNrOfQualifiers()
    {
        if ($this->getQualifyGroup() === null) {
            return 0;
        }
        if (!$this->isBorderPoule()) {
            return count($this->getPlaces());
        }
        return count($this->getPlaces()) - ($this->getQualifyGroup()->getNrOfToPlacesTooMuch());
    }
}
