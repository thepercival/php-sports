<?php

namespace Sports\Qualify\Rule\Horizontal;

use Exception;
use Sports\Place;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Rule\Horizontal as HorizontalQualifyRule;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use Sports\Qualify\QualifyTarget;

class Multiple extends HorizontalQualifyRule implements MultipleQualifyRule
{
    /**
     * @param HorizontalPoule $fromHorizontalPoule
     * @param QualifyGroup $group
     * @param list<Place> $toPlaces
     */
    public function __construct(
        HorizontalPoule $fromHorizontalPoule,
        QualifyGroup $group,
        private array $toPlaces
    ) {
        parent::__construct($fromHorizontalPoule);
        $this->fromHorizontalPoule->setQualifyRuleNew($this);
        $group->setMultipleRule($this);
    }

    public function hasToPlace(Place $place): bool
    {
        return array_search($place, $this->toPlaces, true) !== false;
    }

    public function getAbsoluteRankByToPlace(Place $toPlace): int
    {
        $index = array_search($toPlace, $this->toPlaces, true);
        if( $this->getQualifyTarget() === QualifyTarget::Losers ) {
            $nrOfHorPlaces = $this->getFromHorizontalPoule()->getPlaces()->count();
            return $index === false ? 0 : $nrOfHorPlaces - $index;
        }
        return $index === false ? 0 : $index + 1;
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

    /*public function getNrOfDropouts(): int {
        return $this->fromHorizontalPoule->getPlaces()->count() - $this->getNrOfToPlaces();
    }*/

    public function detach(): void
    {
        $this->getFromHorizontalPoule()->setQualifyRuleNew(null);
    }

    public function getGroup(): QualifyGroup
    {
        $target = $this->getQualifyTarget();
        $targetGroups = $this->getFromRound()->getTargetQualifyGroups($target);
        $qualifGroups = $targetGroups->filter(function (QualifyGroup $qualifyGroup): bool {
            return $this === $qualifyGroup->getMultipleRule();
        });
        $qualifGroup = $qualifGroups->last();
        if ($qualifGroup === false) {
            throw new Exception('voor de multiple-kwalificatieregel kan geen groep worden gevonden', E_ERROR);
        }
        return $qualifGroup;
    }
}
