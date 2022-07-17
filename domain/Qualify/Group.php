<?php

declare(strict_types=1);

namespace Sports\Qualify;

use Exception;
use Sports\Place;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Round;
use Sports\Structure\Cell as StructureCell;
use SportsHelpers\Identifiable;

class Group extends Identifiable
{
    protected int $number;
    protected Round $childRound;
    protected SingleQualifyRule|null $firstSingleRule = null;
    protected MultipleQualifyRule|null $multipleRule = null;

    public function __construct(
        protected Round $parentRound,
        protected Target $target,
        StructureCell $nextStructureCell,
        int|null $numberAsValue = null
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

    public function getTarget(): Target
    {
        return $this->target;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
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

    public function getFirstSingleRule(): SingleQualifyRule | null
    {
        return $this->firstSingleRule;
    }

    public function setFirstSingleRule(SingleQualifyRule | null $singleRule): void
    {
        $this->firstSingleRule = $singleRule;
    }

    public function getMultipleRule(): MultipleQualifyRule | null
    {
        return $this->multipleRule;
    }

    public function setMultipleRule(MultipleQualifyRule | null $multipleRule): void
    {
        $this->multipleRule = $multipleRule;
    }

    public function getNrOfSingleRules(): int
    {
        if ($this->firstSingleRule === null) {
            return 0;
        }
        return $this->firstSingleRule->getLast()->getNumber();
    }

    public function getNrOfToPlaces(): int
    {
        $nrOfToPlaces = 0;
        $firstSingleRule = $this->getFirstSingleRule();
        if ($firstSingleRule !== null) {
            $nrOfToPlaces = $firstSingleRule->getNrOfToPlaces()
                + $firstSingleRule->getNrOfToPlacesTargetSide(Target::Losers);
        }
        $multipleRule = $this->getMultipleRule();
        if ($multipleRule !== null) {
            $nrOfToPlaces += $multipleRule->getNrOfToPlaces();
        }
        return $nrOfToPlaces;
    }

    public function getRule(Place $toPlace): SingleQualifyRule | MultipleQualifyRule
    {
        $singleRule = $this->firstSingleRule;
        while ($singleRule !== null) {
            try {
                $singleRule->getFromPlace($toPlace);
                return $singleRule;
            } catch (Exception $e) {
                $singleRule = $singleRule->getNext();
            }
        }
        $multipleRule = $this->getMultipleRule();
        if ($multipleRule === null || !$multipleRule->hasToPlace($toPlace)) {
            throw new Exception('de kwalificatieregel kan niet gevonden worden', E_ERROR);
        }
        return $multipleRule;
    }

    public function getFromPlace(Place $toPlace): Place | null
    {
        $singleRule = $this->getRule($toPlace);
        if ($singleRule instanceof SingleQualifyRule) {
            return $singleRule->getFromPlace($toPlace);
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

    public function getTargetNative(): string
    {
        return $this->target->value;
    }

    public function setTargetNative(string $target): void
    {
        $this->target = Target::from($target);
    }
}
