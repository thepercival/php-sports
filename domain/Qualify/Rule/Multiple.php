<?php

namespace Sports\Qualify\Rule;

use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Place;
use Sports\Qualify\Rule as QualifyRule;
use Sports\Qualify\Group as QualifyGroup;

class Multiple extends QualifyRule
{
    /**
     * Multiple constructor.
     * @param HorizontalPoule $fromHorizontalPoule
     * @param QualifyGroup $group
     * @param list<Place> $toPlaces
     */
    public function __construct(
        HorizontalPoule $fromHorizontalPoule,
        QualifyGroup $group,
        private array $toPlaces
    )
    {
        parent::__construct($fromHorizontalPoule);
        $this->fromHorizontalPoule->setQualifyRule($this);
        $group->setMultipleRule($this);
    }

    public function hasToPlace(Place $place): bool
    {
        return $this->toPlaces->indexOf($place) >= 0;
    }

    /**
     * @return list<Place>
     */
    public function getToPlaces(): array
    {
        return $this->toPlaces;
    }

    public function getNrOfToPlaces(): int
    {
        return count($this->toPlaces);
    }

    public function detach()
    {
        $this->getFromHorizontalPoule()->setQualifyRule(null);
    }
}
