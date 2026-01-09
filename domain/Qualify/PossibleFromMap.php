<?php

declare(strict_types=1);

namespace Sports\Qualify;

use Doctrine\Common\Collections\Collection;
use Sports\Place;
use Sports\Poule;
use Sports\Qualify\Mapping\ByPlace as QualifyByPlaceMapping;
use Sports\Qualify\Mapping\ByRank as QualifyByRankMapping;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Rule\MultipleQualifyRuleInterface as MultipleRule;
use Sports\Round;

final class PossibleFromMap
{
    /**
     * @var array<int, list<Poule>>
     */
    protected array $map = [];
    // protected self|null $parent = null;
    protected bool $empty = true;

    public function __construct(protected Round $leafRound, bool $initMap = false)
    {
        foreach ($leafRound->getPoules() as $childPoule) {
            $this->map[$childPoule->getNumber()] = [];
        }
        if ($initMap) {
            $this->initMap($leafRound);
        }
    }

    protected function initMap(Round $round): void
    {
        $qualifyGroup = $round->getParentQualifyGroup();
        if ($qualifyGroup === null) {
            return;
        }
        $this->addGroup($qualifyGroup);
    }

    protected function addGroup(QualifyGroup $group): void
    {
        if( $group->getDistribution() === QualifyDistribution::Vertical ) {
            if( $group->getFirstSingleRule() !== null || $group->getMultipleRule() !== null ) {
                $this->addGroupToMap($group);
            }
        } else {
            $singleRule = $group->getFirstSingleRule();
            while ($singleRule !== null) {
                foreach ($singleRule->getMappings() as $mapping) {
                    if( $mapping instanceof QualifyByPlaceMapping) {
                        $this->addMappingToMap($mapping);
                    }
                }
                $singleRule = $singleRule->getNext();
            }
            $multipRule = $group->getMultipleRule();
            if ($multipRule !== null) {
                $this->addGroupToMap($group);
            }
        }
    }

    public function createParent(): PossibleFromMap|null
    {
        $parentQualifyGroup = $this->leafRound->getParentQualifyGroup();
        if ($parentQualifyGroup === null) {
            return null;
        }
        $grandParentQualifyGroup = $parentQualifyGroup->getParentRound()->getParentQualifyGroup();
        if ($grandParentQualifyGroup === null) {
            return null;
        }
        return new PossibleFromMap($parentQualifyGroup->getParentRound(), true);
    }

//    public function addSingleRule(SingleRule $rule): void
//    {
//        $this->addPlaceMappings($rule->getMappings());
//    }
//
//    /**
//     * @param Collection<int, QualifyPlaceMapping> $placeMappings
//     */
//    public function addPlaceMappings(Collection $placeMappings): void
//    {
//        foreach ($placeMappings as $placeMapping) {
//            $childPouleNumber = $placeMapping->getToPlace()->getPoule()->getNumber();
//            $this->map[$childPouleNumber][] = $placeMapping->getFromPlace()->getPoule();
//        }
//    }

    public function addMappingToMap(QualifyByPlaceMapping $placeMapping): void
    {
        $this->empty = false;
        $childPouleNumber = $placeMapping->getToPlace()->getPoule()->getNumber();
        $this->map[$childPouleNumber][] = $placeMapping->getFromPoule();
    }

    public function addGroupToMap(QualifyGroup $group): void
    {
        $this->empty = false;
        $parentPoules = array_values($group->getParentRound()->getPoules()->toArray());
        foreach ($group->getChildRound()->getPoules() as $childPoule) {
            $this->map[$childPoule->getNumber()] = $parentPoules;
        }
    }

    /**
     * @param Poule $childPoule
     * @return list<Poule>
     */
    public function getFromPoules(Poule $childPoule): array
    {
        return $this->map[$childPoule->getNumber()];
    }

    public function isEmpty(): bool
    {
        return $this->empty;
    }
}
