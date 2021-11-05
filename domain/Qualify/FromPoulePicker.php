<?php

declare(strict_types=1);

namespace Sports\Qualify;

use Sports\Output\StructureOutput;
use Sports\Poule;
use Sports\Place;
use Sports\Qualify\PlaceMapping as QualifyPlaceMapping;

class FromPoulePicker
{
    protected PossibleFromMap|null $possibleParentFromMap = null;

    public function __construct(protected PossibleFromMap $possibleFromMap)
    {
    }

    /**
     * @param Poule $childPoule
     * @param list<Poule> $avaiableFromPoules
     * @param list<Poule> $otherChildRoundPoules
     * @return Poule
     */
    public function getBestFromPoule(
        Poule $childPoule,
        array $avaiableFromPoules,
        array $otherChildRoundPoules
    ): Poule {
        if ($this->possibleFromMap->isEmpty()) {
            $bestFromPoule = reset($avaiableFromPoules);
            if ($bestFromPoule !== false) {
                return $bestFromPoule;
            }
        }
//        if ($childPoule->getRound()->getNumber()->getNumber() === 3 ) {
//            // (new StructureOutput())->output($childPoule->getRound()->getNumber()->getStructure());
//            $erree = 12;
//        }
//        if ($childPoule->getRound()->getNumber()->getNumber() === 3 && $childPoule->getNumber() === 4) {
//            // (new StructureOutput())->output($childPoule->getRound()->getNumber()->getStructure());
//            $erree = 12;
//        }

        $bestFromPoules = $this->getFewestOverlapses($childPoule, $avaiableFromPoules);

        if (count($bestFromPoules) < 2) {
            $bestFromPoule = reset($bestFromPoules);
            if ($bestFromPoule !== false) {
                return $bestFromPoule;
            }
        }
        $fromPlacesWithMostOtherPouleOrigins = $this->getMostOverlapses($otherChildRoundPoules, $bestFromPoules);
        $fromPlacesWithMostOtherPouleOrigins = reset($fromPlacesWithMostOtherPouleOrigins);
        if ($fromPlacesWithMostOtherPouleOrigins === false) {
            throw new \Exception('could not find best pick', E_ERROR);
        }
        return $fromPlacesWithMostOtherPouleOrigins;
    }

    /**
     * @param Poule $p_childPoule
     * @param list<Poule> $availableFromPoules
     * @return list<Poule>
     */
    protected function getFewestOverlapses(Poule $p_childPoule, array $availableFromPoules): array
    {
        $bestFromPoules = $this->getFewestOverlapsesHelper($p_childPoule, $availableFromPoules, $this->possibleFromMap);
        if (count($bestFromPoules) < 2) {
            return $bestFromPoules;
        }

        $possibleParentFromMap = $this->getParentPossibleFromMap();
        if ($possibleParentFromMap === null) {
            return $bestFromPoules;
        }
        $alreadyUsedGrandParentPoules = $this->getGrantParentPossiblePoules($p_childPoule);
        $fewestOverlapses = null;

        $veryBestFromPoules = [];
        foreach ($bestFromPoules as $bestFromPoule) {
            $nrOfOverlapses = 0;
            foreach ($alreadyUsedGrandParentPoules as $alreadyUsedGrandParentPoule) {
                $nrOfOverlapses += $this->getNrOfPossibleOverlapses($alreadyUsedGrandParentPoule, $bestFromPoule, $possibleParentFromMap);
            }
//            $bestFromParentPoules = $this->getFewestOverlapsesHelper(
//                $bestFromPoule,
//                $alreadyUsedGrandParentPoules,
//                $possibleParentFromMap
//            );
            // $x = array_intersect => $bestFromParentPoules, $possibleParentFromPoules,
//            $nrOfOverlapses = count($bestFromParentPoules);
            if ($fewestOverlapses === null || $nrOfOverlapses < $fewestOverlapses) {
                $veryBestFromPoules = [$bestFromPoule];
                $fewestOverlapses = $nrOfOverlapses;
            } elseif ($fewestOverlapses === $nrOfOverlapses) {
                array_push($veryBestFromPoules, $bestFromPoule);
            }
        }
        return $veryBestFromPoules;
    }

    /**
     * @param Poule $childPoule
     * @param list<Poule> $availableFromPoules
     * @return list<Poule>
     */
    protected function getFewestOverlapsesHelper(Poule $childPoule, array $availableFromPoules, PossibleFromMap $possibleFromMap): array
    {
        // $toPoule = $toPlace->getPoule();
        $bestFromPoules = [];
        $fewestOverlapses = null;
        // $possibleFromPoules = $this->possibleFromMap->getFromPoules($childPoule);
        foreach ($availableFromPoules as $availableFromPoule) {
            $nrOfOverlapses = $this->getNrOfPossibleOverlapses($availableFromPoule, $childPoule, $possibleFromMap);
            if ($fewestOverlapses === null || $nrOfOverlapses < $fewestOverlapses) {
                $bestFromPoules = [$availableFromPoule];
                $fewestOverlapses = $nrOfOverlapses;
            } elseif ($fewestOverlapses === $nrOfOverlapses) {
                array_push($bestFromPoules, $availableFromPoule);
            }
        }
        return $bestFromPoules;
    }

    /**
     * @param Poule $childPoule
     * @return list<Poule>
     */
    protected function getGrantParentPossiblePoules(Poule $childPoule): array
    {
        $parentPossibleFromMap = $this->getParentPossibleFromMap();
        if ($parentPossibleFromMap === null) {
            return [];
        }
        $possiblePoules = [];
        $fromPoules = $this->possibleFromMap->getFromPoules($childPoule);
        foreach ($fromPoules as $fromPoule) {
            $possiblePoules = array_merge($possiblePoules, $parentPossibleFromMap->getFromPoules($fromPoule));
        }
        return $possiblePoules;
    }


    /**
     * @param list<Place> $otherChildRoundPlaces
     * @return list<Poule>
     */
    protected function getOtherChildRoundPoules(array $otherChildRoundPlaces): array
    {
        $firstPlace = array_pop($otherChildRoundPlaces);
        $poules = $firstPlace !== null ? [$firstPlace->getPoule()] : [];
        foreach ($otherChildRoundPlaces as $place) {
            if (array_search($place->getPoule(), $poules, true) !== false) {
                array_push($poules, $place->getPoule());
            }
        }
        return $poules;
    }

    /**
     * @param list<Poule> $otherChildPoules
     * @param list<Poule> $availableFromPoules
     * @return list<Poule>
     */
    protected function getMostOverlapses(array $otherChildPoules, array $availableFromPoules): array
    {
        // $toPoule = $toPlace->getPoule();
        $bestFromPoules = [];
        $mostOverlapses = null;
        // $possibleFromPoules = $this->possibleFromMap->getFromPoules($childPoule);
        foreach ($availableFromPoules as $avaiableFromPoule) {
            foreach ($otherChildPoules as $otherChildPoule) {
                $nrOfOverlapses = $this->getNrOfPossibleOverlapses($avaiableFromPoule, $otherChildPoule, $this->possibleFromMap);
                //            $overlapses = array_filter($possibleFromPoules, function (Poule $possibleFromPoule) use ($avaiableFromPoule): bool {
                //                return $possibleFromPoule === $avaiableFromPoule;
                //            });
                if ($mostOverlapses === null || $nrOfOverlapses > $mostOverlapses) {
                    $bestFromPoules = [$avaiableFromPoule];
                    $mostOverlapses = $nrOfOverlapses;
                } elseif ($mostOverlapses === $nrOfOverlapses) {
                    array_push($bestFromPoules, $avaiableFromPoule);
                }
            }
        }
        return $bestFromPoules;
    }

    protected function getNrOfPossibleOverlapses(Poule $fromPoule, Poule $childPoule, PossibleFromMap $possibleFromMap): int
    {
        $possibleFromPoules = $possibleFromMap->getFromPoules($childPoule);
        $overlapses = array_filter($possibleFromPoules, function (Poule $possibleFromPoule) use ($fromPoule): bool {
            return $possibleFromPoule === $fromPoule;
        });
        return count($overlapses);
    }

    protected function getParentPossibleFromMap(): PossibleFromMap|null
    {
        if ($this->possibleParentFromMap === null) {
            $this->possibleParentFromMap = $this->possibleFromMap->createParent();
        }
        return $this->possibleParentFromMap;
    }
}
