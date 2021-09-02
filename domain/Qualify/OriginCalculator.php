<?php
declare(strict_types=1);

namespace Sports\Qualify;

use Exception;
use Sports\Poule;
use Sports\Place;
use Sports\Qualify\Rule\Single as SingleQualifyRule;

class OriginCalculator
{
    /**
     * @var array<string, list<Poule>>
     */
    private array $possiblePreviousPoulesCache = [];

    public function getPossibleOverlapses(Poule $poule1, Poule $poule2): int
    {
        $possibleOriginsMap = [];
        $this->addPossibleOriginMap($poule1, $possibleOriginsMap);
        $this->fillPossibleOriginMap($poule1, $possibleOriginsMap);

        $possibleOrigins = $this->getPossibleOrigins($poule2);
        array_unshift($possibleOrigins, $poule2);
        $filtered = array_filter($possibleOrigins, function (Poule $originPoule) use ($possibleOriginsMap): bool {
            return isset($possibleOriginsMap[$originPoule->getStructureLocation()]);
        });
        return count($filtered);
    }

    /**
     * @param Poule $poule
     * @return list<Poule>
     */
    public function getPossibleOrigins(Poule $poule): array
    {
        $previousOrigins = $this->getPossiblePreviousPoules($poule);
        if (count($previousOrigins) === 0) {
            return [];
        }
        $origins = [];
        foreach ($previousOrigins as $previousOrigin) {
            $this->addPossibleOriginToList($previousOrigin, $origins);
            foreach ($this->getPossibleOrigins($previousOrigin) as $previousPreviousOrigin) {
                $this->addPossibleOriginToList($previousPreviousOrigin, $origins);
            }
        }
        return $origins;
    }

    /**
     * @param Poule $poule
     * @param array<int|string, Poule> $originMap
     */
    protected function fillPossibleOriginMap(Poule $poule, array $originMap): void
    {
        $previousOrigins = $this->getPossiblePreviousPoules($poule);
        if (count($previousOrigins) === 0) {
            return;
        }
        foreach ($previousOrigins as $previousOrigin) {
            $this->addPossibleOriginMap($previousOrigin, $originMap);
            $this->fillPossibleOriginMap($previousOrigin, $originMap);
        }
    }

    /**
     * @param Poule $poule
     * @param list<Poule> $origins
     */
    protected function addPossibleOriginToList(Poule $poule, array &$origins): void
    {
        if (array_search($poule, $origins, true) === false) {
            array_push($origins, $poule);
        }
    }

    /**
     * @param Poule $poule
     * @param array<int|string, Poule> $origins
     */
    protected function addPossibleOriginMap(Poule $poule, array &$origins): void
    {
        $origins[$poule->getStructureLocation()] = $poule;
    }

    /**
     * @param Poule $poule
     * @return list<Poule>
     */
    protected function getPossiblePreviousPoules(Poule $poule): array
    {
        $strucureLocation = $poule->getStructureLocation();
        if (isset($this->possiblePreviousPoulesCache[$strucureLocation])) {
            return $this->possiblePreviousPoulesCache[$strucureLocation];
        }

        $parentQualifyGroup = $poule->getRound()->getParentQualifyGroup();
        if ($parentQualifyGroup !== null && $parentQualifyGroup->getMultipleRule() !== null) {
            $possiblePreviousPoules = $parentQualifyGroup->getParentRound()->getPoules()->toArray();
        } else {
            $possiblePreviousPoules = [];
            foreach ($poule->getPlaces() as $place) {
                $possiblePreviousPoules = array_merge(
                    $possiblePreviousPoules,
                    $this->getPlacePossiblePreviousPoules($place)
                );
            }
        }
        $possiblePreviousPoules = array_values($possiblePreviousPoules);
        $this->possiblePreviousPoulesCache[$strucureLocation] = $possiblePreviousPoules;
        return $possiblePreviousPoules;
    }

    /**
     * @param Place $place
     * @return list<Poule>
     * @throws \Exception
     */
    protected function getPlacePossiblePreviousPoules(Place $place): array
    {
        $parentQualifyGroup = $place->getRound()->getParentQualifyGroup();
        if ($parentQualifyGroup === null) {
            return [];
        }
        try {
            $rule = $parentQualifyGroup->getRule($place);
            if ($rule instanceof SingleQualifyRule) {
                return [$rule->getFromPlace($place)->getPoule()];
            }
            return array_values($parentQualifyGroup->getParentRound()->getPoules()->toArray());
        } catch (Exception $e) {
            return [];
        }
    }
}
