<?php

namespace Sports\Qualify\Rule;

use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Place;
use Sports\Qualify\Rule as QualifyRule;
use Sports\Round;

class Multiple extends QualifyRule
{
    /**
     * @var array | Place[]
     */
    private $toPlaces;
    /**
     * @var HorizontalPoule
     */
    private $fromHorizontalPoule;
    /**
     * @var int
     */
    private $nrOfToPlaces;

    public function __construct(HorizontalPoule $fromHorizontalPoule, int $nrOfToPlaces)
    {
        $this->fromHorizontalPoule = $fromHorizontalPoule;
        $this->fromHorizontalPoule->setQualifyRuleMultiple($this);
        $this->nrOfToPlaces = $nrOfToPlaces;
        $this->toPlaces = [];
    }

    public function getFromHorizontalPoule(): HorizontalPoule
    {
        return $this->fromHorizontalPoule;
    }

    public function getFromRound(): Round
    {
        return $this->fromHorizontalPoule->getRound();
    }

    public function isMultiple(): bool
    {
        return true;
    }

    public function isSingle(): bool
    {
        return false;
    }

    public function getWinnersOrLosers(): int
    {
        return $this->fromHorizontalPoule->getQualifyGroup()->getWinnersOrLosers();
    }

    public function addToPlace(Place $toPlace)
    {
        $this->toPlaces[] = $toPlace;
        $toPlace->setFromQualifyRule($this);
    }

    public function toPlacesComplete(): bool
    {
        return $this->nrOfToPlaces === count($this->toPlaces);
    }

    /**
     * @return array | Place[]
     */
    public function getToPlaces(): array
    {
        return $this->toPlaces;
    }

    public function getFromPlaceNumber(): int
    {
        return $this->getFromHorizontalPoule()->getPlaceNumber();
    }
}
