<?php

namespace Sports\Qualify\Rule;

use Sports\Poule;
use Sports\Place;
use Sports\Qualify\RuleOld as QualifyRule;
use Sports\Round;
use Sports\Qualify\Group as QualifyGroup;

class Single extends QualifyRule
{
    private Place|null $toPlace = null;
    /**
     * @var int
     */
    private $winnersOrLosers;

    public function __construct(private Place $fromPlace, QualifyGroup $toQualifyGroup)
    {
        $this->winnersOrLosers = $toQualifyGroup->getWinnersOrLosers();
        $this->fromPlace->setToQualifyRule($toQualifyGroup->getWinnersOrLosers(), $this);
    }

    public function getFromRound(): Round
    {
        return $this->fromPlace->getRound();
    }

    public function getWinnersOrLosers(): int
    {
        return $this->winnersOrLosers;
    }

    public function getFromPlace(): Place
    {
        return $this->fromPlace;
    }

    public function getFromPoule(): Poule
    {
        return $this->fromPlace->getPoule();
    }

    public function getToPlace(): Place
    {
        if ($this->toPlace === null) {
            throw new \Exception('toPlace should be set, before getting it', E_ERROR);
        }
        return $this->toPlace;
    }

    public function setToPlace(Place $toPlace): void
    {
        $this->toPlace = $toPlace;
        $toPlace->setFromQualifyRule($this);
    }

    public function getFromPlaceNumber(): int
    {
        if ($this->getWinnersOrLosers() === QualifyGroup::WINNERS) {
            return $this->getFromPlace()->getNumber();
        }
        return ($this->getFromPlace()->getPoule()->getPlaces()->count() - $this->getFromPlace()->getNumber()) + 1;
    }
}
