<?php

namespace Sports\Qualify\Rule;

use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Place;
use Sports\Qualify\RuleOld as QualifyRule;
use Sports\Round;

class Multiple extends QualifyRule
{
    /**
     * @var array<Place>
     */
    private array $toPlaces;

    public function __construct(private HorizontalPoule $fromHorizontalPoule, private int $nrOfToPlaces)
    {
        $this->fromHorizontalPoule->setMultipleQualifyRule($this);
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

    public function getWinnersOrLosers(): int
    {
        return $this->fromHorizontalPoule->getQualifyGroup()->getWinnersOrLosers();
    }

    public function addToPlace(Place $toPlace): void
    {
        $this->toPlaces[] = $toPlace;
        $toPlace->setFromQualifyRule($this);
    }

    public function toPlacesComplete(): bool
    {
        return $this->nrOfToPlaces === count($this->toPlaces);
    }

    /**
     * @return array<Place>
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
