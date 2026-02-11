<?php

declare(strict_types=1);

namespace Sports\Structure;

use Sports\Place;
use Sports\Qualify\QualifyTarget;
use Exception;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Round;
use SportsHelpers\PouleStructures\ValidMinimumBalancedPouleStructure;
use SportsHelpers\PouleStructures\PouleStructure;

final class RemovalValidator
{
//    protected NameService $nameService;

    public function __construct()
    {
//        $this->nameService = new NameService();
    }

    /**
     * @param Round $round
     * @param array<string, int> $nrOfPlacesToRemoveMap
     * @param int $minNrOfPlacesPerPoule
     * @throws Exception
     */
    public function willStructureBeValid(
        Round $round,
        array $nrOfPlacesToRemoveMap,
        int $minNrOfPlacesPerPoule
    ): void {
        // determine which qualifyGroup needs to remove how many places

//        per qualiygroup bepalen welke placeLocations verwijderd moeten worden!
//    vanuit de place kun je bij de qualifygroup komen!!


//        if ($newPouleStructure->getNrOfPlaces() < $round->getNrOfPlacesChildren()) {
//            throw new Exception(
//                'er blijven te weinig deelnemers over om naar de volgende ronde gaan',
//                E_ERROR
//            );
//        }
//        if ($newPouleStructure->getSmallestPoule() < $this->getMinPlacesPerPouleSmall()) {
//            throw new Exception(
//                'de poulegrootte wordt te klein in de volgende ronde, pas dit eerst aan',
//                E_ERROR
//            );
//        }

        foreach ([QualifyTarget::Winners, QualifyTarget::Losers] as $qualifyTarget) {
            $qualifyGroups = $round->getTargetQualifyGroups($qualifyTarget);
//            if (count($qualifyGroups) < 2) {
//                continue;
//            }
            foreach ($qualifyGroups as $qualifyGroup) {
                $childRound = $qualifyGroup->getChildRound();
                $qualifyGroupIdx = $this->getQualifyGroupIndex($qualifyGroup);
                $nrOfPlacesToRemove = 0;
                if (isset($nrOfPlacesToRemoveMap[$qualifyGroupIdx])) {
                    $nrOfPlacesToRemove = $nrOfPlacesToRemoveMap[$qualifyGroupIdx];
                }
                if ($nrOfPlacesToRemove === 0) {
                    continue;
                }

                $poules = $childRound->createPouleStructure()->toArray();
                $newChildPouleStructure = new ValidMinimumBalancedPouleStructure($minNrOfPlacesPerPoule, ...$poules);
                while ($nrOfPlacesToRemove--) {
                    $newChildPouleStructure = $newChildPouleStructure->removePlace2();
                }
                $placesToRemove = $this->getRemovedPlaces($childRound, $newChildPouleStructure);
                $nrOfPlacesToRemoveMap = $this->getNrOfPlacesToRemoveMap($round, $placesToRemove);
                $this->willStructureBeValid($childRound, $nrOfPlacesToRemoveMap, $minNrOfPlacesPerPoule);
            }
        }
    }

    public function getQualifyGroupIndex(QualifyGroup $qualifyGroup): string
    {
        return $qualifyGroup->getTarget()->value . $qualifyGroup->getNumber();
    }

    /**
     * @param Round $round
     * @param ValidMinimumBalancedPouleStructure $pouleStructure
     * @return list<Place>
     */
    private function getRemovedPlaces(Round $round, ValidMinimumBalancedPouleStructure $pouleStructure): array
    {
        $removedPlaces = [];
        $pouleStructureasArray = $pouleStructure->toArray();
        foreach ($round->getPoules() as $poule) {
            if (count($pouleStructureasArray) === 0) {
                $removedPlaces = array_values(array_merge($removedPlaces, $poule->getPlaces()->toArray()));
            } else {
                $nrOfPlaces = array_shift($pouleStructureasArray);
                foreach ($poule->getPlaces() as $place) {
                    if ($place->getPlaceNr() > $nrOfPlaces) {
                        $removedPlaces[] = $place;
                    }
                }
            }
        }
        return $removedPlaces;
    }

    /**
     * @param Round $round
     * @param list<Place> $placesToRemove
     * @return array<string, int>
     */
    public function getNrOfPlacesToRemoveMap(Round $round, array $placesToRemove): array
    {
        $map = [];
        foreach ($round->getQualifyGroups() as $qualifyGroup) {
            $idx = $this->getQualifyGroupIndex($qualifyGroup);
            $map[$idx] = $this->getQualifyGroupNrOfPlacesToRemove($placesToRemove, $qualifyGroup);
        }
        return $map;
    }

    /**
     * @param list<Place> $placesToRemove
     * @param QualifyGroup $childQualifyGroup
     * @return int
     * @throws Exception
     */
    private function getQualifyGroupNrOfPlacesToRemove(array $placesToRemove, QualifyGroup $childQualifyGroup): int
    {
        $nrOfPlaces = 0;
        // determine which qualifyGroup needs to remove how many places
        foreach ($placesToRemove as $placeIt) {
            $horPoule = $placeIt->getHorizontalPoule($childQualifyGroup->getTarget());

            // if( $childQualifyGroup->getDistribution() === QualifyDistribution::HorizontalSnake) {
                $singleQualifyRule = $childQualifyGroup->getFirstSingleRule();
                while ($singleQualifyRule) {
                    if ($singleQualifyRule->getFromHorizontalPoule() === $horPoule) {
                        $nrOfPlaces++;
                    }
                    $singleQualifyRule = $singleQualifyRule->getNext();
                }
//            } else {
//                throw new \Exception('getQualifyGroupNrOfPlacesToRemove', E_ERROR); // @TODO CDK
//            }


//            $multipleQualifyRule = $childQualifyGroup->getMultipleRule();
//            if ($multipleQualifyRule && $multipleQualifyRule->getFromHorizontalPoule() === $horPoule) {
//                $nrOfPlaces++;
//            }
        }
        return $nrOfPlaces;
    }

//    protected function validatePouleSizes(BalancedPouleStructure $pouleStructure, int $minNrOfPlacesPerPoule): void {
//        foreach( $pouleStructure->toArray() as $nrOfPlaces) {
//            if( $nrOfPlaces < $minNrOfPlacesPerPoule ) {
//                throw new \Exception('poules have not have enough places', E_ERROR);
//            }
//        }
//    }
}
