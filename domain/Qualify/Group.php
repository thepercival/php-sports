<?php

declare(strict_types=1);

namespace Sports\Qualify;

use Exception;
use Sports\Place;
use Sports\Qualify\Rule\Horizontal\Multiple as HorizontalMultipleQualifyRule;
use Sports\Qualify\Rule\Horizontal\Single as HorizontalSingleQualifyRule;
use Sports\Qualify\Rule\Vertical\Multiple as VerticalMultipleQualifyRule;
use Sports\Qualify\Rule\Vertical\Single as VerticalSingleQualifyRule;
use Sports\Round;
use Sports\Qualify\QualifyTarget as QualifyTarget;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Structure\StructureCell as StructureCell;
use SportsHelpers\Identifiable;

class Group extends Identifiable
{
    protected int $number;
    protected Round $childRound;
    protected QualifyDistribution $distribution = QualifyDistribution::HorizontalSnake;
    protected HorizontalSingleQualifyRule|VerticalSingleQualifyRule|null $firstSingleRule = null;
    protected HorizontalMultipleQualifyRule|VerticalMultipleQualifyRule|null $multipleRule = null;

    public function __construct(
        protected Round         $parentRound,
        protected QualifyTarget $target,
        StructureCell           $nextStructureCell,
        int|null                $numberAsValue = null
    ) {
        if ($numberAsValue !== null) {
            $this->number = $numberAsValue;
            $this->insertQualifyGroupAt($parentRound, $numberAsValue);
        } else {
            $this->number = $parentRound->getTargetQualifyGroups($target)->count() + 1;
            $this->addQualifyGroup($parentRound);
        }
        $this->childRound = new Round($nextStructureCell, $this);
    }

    public function getTarget(): QualifyTarget
    {
        return $this->target;
    }

    public function setTarget(QualifyTarget $target): void
    {
        $this->target = $target;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    public function getDistribution(): QualifyDistribution {
        return $this->distribution;
    }

    public function setDistribution(QualifyDistribution $distribution): void {
        $this->distribution = $distribution;
    }

    public function getParentRound(): Round
    {
        return $this->parentRound;
    }

    protected function insertQualifyGroupAt(Round $round, int $insertAt): void
    {
        $qualifyGroups = $round->getTargetQualifyGroups($this->getTarget());
        if (!$qualifyGroups->contains($this)) {
            $round->addQualifyGroup($this);
            // sort auto because of sort-config in db-yml
        }
    }

    public function addQualifyGroup(Round $round): void
    {
        $qualifyGroups = $round->getTargetQualifyGroups($this->getTarget());
        if (!$qualifyGroups->contains($this)) {
            $round->addQualifyGroup($this);
        }
    }

    public function getChildRound(): Round
    {
        return $this->childRound;
    }

    public function getFirstSingleRule(): HorizontalSingleQualifyRule|VerticalSingleQualifyRule|null
    {
        return $this->firstSingleRule;
    }

    public function setFirstSingleRule(HorizontalSingleQualifyRule|VerticalSingleQualifyRule|null $singleRule): void
    {
        $this->firstSingleRule = $singleRule;
    }

    public function getMultipleRule(): HorizontalMultipleQualifyRule|VerticalMultipleQualifyRule|null
    {
        return $this->multipleRule;
    }

    public function setMultipleRule(HorizontalMultipleQualifyRule|VerticalMultipleQualifyRule|null $multipleRule): void
    {
        $this->multipleRule = $multipleRule;
    }


//    public function getNrOfSingleRules(): int
//    {
//        $nrOfSingleRules = 0;
//
//        $singleRule = $this->firstSingleRule;
//        while  ($singleRule !== null) {
//            $nrOfSingleRules++;
//            $singleRule = $singleRule->getNext();
//        }
//        return $nrOfSingleRules;
//    }


    public function getRulesNrOfToPlaces(): int {
        $nrOfToPlaces = 0;

        $singleRule = $this->getFirstSingleRule();
        while ($singleRule !== null) {
            $nrOfToPlaces += $singleRule->getNrOfMappings();
            $singleRule = $singleRule->getNext();
        }
        $multipleRule = $this->getMultipleRule();
        if ($multipleRule !== null) {
            $nrOfToPlaces += $multipleRule->getNrOfToPlaces();
        }

        return $nrOfToPlaces;
    }

    public function getNext(): QualifyGroup | null {
        return $this->getParentRound()->getQualifyGroup($this->getTarget(), $this->getNumber() + 1);
    }

    public function getRuleByToPlace(Place $toPlace): HorizontalSingleQualifyRule | HorizontalMultipleQualifyRule | VerticalSingleQualifyRule | VerticalMultipleQualifyRule {
//        if( $this->distribution === Distribution::Vertical) {
//            $verticalRule = $this->firstVertRule;
//            while ($verticalRule !== null) {
//                if($verticalRule->hasToPlace($toPlace)) {
//                    return $verticalRule;
//                }
//                $verticalRule = $verticalRule->getNext();
//            }
//            throw new \Exception('de verticale kwalificatieregel kan niet gevonden worden', E_ERROR);
//        }
        $singleRule = $this->firstSingleRule;
        while ($singleRule !== null) {
            if ($singleRule->getMappingByToPlace($toPlace) !== null ) {
                return $singleRule;
            }
            $singleRule = $singleRule->getNext();
        }
        $multipleRule = $this->getMultipleRule();
        if ($multipleRule === null || !$multipleRule->hasToPlace($toPlace)) {
            throw new \Exception('de horizontale multiple kwalificatieregel kan niet gevonden worden', E_ERROR);
        }
        return $multipleRule;
    }

    public function getFromPlace(Place $toPlace): Place | null
    {
        $singleRule = $this->getRuleByToPlace($toPlace);
        if ($singleRule instanceof HorizontalSingleQualifyRule) {
            $mapping = $singleRule->getMappingByToPlace($toPlace);
            return $mapping?->getFromPlace();
        }
        return null;
    }

    public function isBorderGroup(): bool
    {
        $qualifyGroups = $this->getParentRound()->getTargetQualifyGroups($this->getTarget());
        return $this === $qualifyGroups->last();
    }

    public function detach(): void
    {
        $this->detachRules();
        $qualifyGroups = $this->getParentRound()->getQualifyGroups();
        $qualifyGroups->removeElement($this);
        $this->getChildRound()->detach();
    }

    public function detachRules(): void
    {
        if ($this->multipleRule !== null) {
            $this->multipleRule->detach();
            $this->multipleRule = null;
        }
        if ($this->firstSingleRule !== null) {
            $this->firstSingleRule->detach();
            $this->firstSingleRule = null;
        }
    }
}
