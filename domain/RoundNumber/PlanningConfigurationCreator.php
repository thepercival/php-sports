<?php

declare(strict_types=1);

namespace Sports\Round\Number;

use Sports\Planning\GameAmountConfig;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\PlanningConfigurationModerator;
use SportsPlanning\PlanningPouleStructure;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

final class PlanningConfigurationCreator
{
    public function __construct()
    {
    }

    public function create(RoundNumber $roundNumber, RefereeInfo|null $refereeInfo): PlanningConfiguration
    {
        $config = $roundNumber->getValidPlanningConfig();


        $pouleStructure = $roundNumber->createPouleStructure();
//        $roundNumber->createSports();
        $sportsWithNrOfFieldsAndNrOfCycles = $roundNumber->createSportWithNrOfFieldsAndNrOfCycles();

        return (new PlanningConfigurationModerator())->createReducedAndValidatedConfiguration(
            $pouleStructure,
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            $config->getPerPoule()
        );
    }

//
//
//    protected function createPouleStructure(RoundNumber $roundNumber): PouleStructure
//    {
//        $nrOfPlacesPerPoule = [];
//        foreach ($roundNumber->getPoules() as $poule) {
//            $nrOfPlacesPerPoule[] = $poule->getPlaces()->count();
//        }
//        return new PouleStructure(...$nrOfPlacesPerPoule);
//    }
//
//    /**
//     * @param PouleStructure $pouleStructure
//     * @param list<SportPersistVariantWithNrOfFields> $sportPersistVariantsWithFields
//     * @param RefereeInfo $refereeInfo
//     * @return list<SportPersistVariantWithNrOfFields>
//     */
//    protected function reduceFields(
//        PouleStructure $pouleStructure,
//        array $sportPersistVariantsWithFields,
//        RefereeInfo $refereeInfo
//    ): array {
//        $planningPouleStructure = new PlanningPouleStructure(
//            $pouleStructure,
//            $sportPersistVariantsWithFields,
//            $refereeInfo
//        );
//        $maxNrOfGamesPerBatch = $planningPouleStructure->getMaxNrOfGamesPerBatch();
//        $reducedSportVariants = [];
//        foreach ($sportPersistVariantsWithFields as $sportVariantWithField) {
//            $reducedNrOfFields = $sportVariantWithField->nrOfFields;
//            if ($reducedNrOfFields > $maxNrOfGamesPerBatch) {
//                $reducedNrOfFields = $maxNrOfGamesPerBatch;
//            }
//            $reducedSportVariants[] = new SportPersistVariantWithNrOfFields(
//                $sportVariantWithField->createSportVariant(),
//                $reducedNrOfFields
//            );
//        }
//
//        $moreReducedSportVariants = $this->reduceFieldsBySports($pouleStructure, $reducedSportVariants);
//
//        usort(
//            $moreReducedSportVariants,
//            function (SportPersistVariantWithNrOfFields $sportA, SportPersistVariantWithNrOfFields $sportB): int {
//                return $sportA->nrOfFields > $sportB->nrOfFields ? -1 : 1;
//            }
//        );
//        return $moreReducedSportVariants;
//    }
//
//    /**
//     * @param PouleStructure $pouleStructure
//     * @param list<SportPersistVariantWithNrOfFields> $sportPersistVariantsWithFields
//     * @return list<SportPersistVariantWithNrOfFields>
//     */
//    protected function reduceFieldsBySports(PouleStructure $pouleStructure, array $sportPersistVariantsWithFields): array
//    {
//        $leastNrOfBatchesNeeded = $this->getLeastNrOfBatchesNeeded($pouleStructure, $sportPersistVariantsWithFields);
//        return array_map(
//            function (SportPersistVariantWithNrOfFields $sportPersistVariantWithFields) use (
//                $pouleStructure,
//                $leastNrOfBatchesNeeded
//            ): SportPersistVariantWithNrOfFields {
//                return $this->reduceSportVariantFields(
//                    $pouleStructure,
//                    $sportPersistVariantWithFields,
//                    $leastNrOfBatchesNeeded
//                );
//            },
//            $sportPersistVariantsWithFields
//        );
//    }
//
//    protected function reduceSportVariantFields(
//        PouleStructure $pouleStructure,
//        SportPersistVariantWithNrOfFields $sportPersistVariantWithFields,
//        int $minNrOfBatches
//    ): SportPersistVariantWithNrOfFields {
//        $sportVariant = $sportPersistVariantWithFields->createSportVariant();
//        $nrOfFields = $sportPersistVariantWithFields->nrOfFields;
//        if ($nrOfFields === 1) {
//            return $sportPersistVariantWithFields;
//        }
//        $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded($pouleStructure, $sportVariant, $nrOfFields);
//        while ($nrOfBatchesNeeded < $minNrOfBatches) {
//            if (--$nrOfFields === 1) {
//                break;
//            }
//            $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded($pouleStructure, $sportVariant, $nrOfFields);
//        }
//        return new SportPersistVariantWithNrOfFields($sportVariant, $nrOfFields);
//    }
//
//    /**
//     * @param PouleStructure $pouleStructure
//     * @param list<SportPersistVariantWithNrOfFields> $sportVariantsWithFields
//     * @return int
//     */
//    protected function getLeastNrOfBatchesNeeded(PouleStructure $pouleStructure, array $sportVariantsWithFields): int
//    {
//        $leastNrOfBatchesNeeded = null;
//        foreach ($sportVariantsWithFields as $sportVariantWithField) {
//            $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded(
//                $pouleStructure,
//                $sportVariantWithField->createSportVariant(),
//                $sportVariantWithField->nrOfFields
//            );
//            if ($leastNrOfBatchesNeeded === null || $nrOfBatchesNeeded > $leastNrOfBatchesNeeded) {
//                $leastNrOfBatchesNeeded = $nrOfBatchesNeeded;
//            }
//        }
//        if ($leastNrOfBatchesNeeded === null) {
//            throw new \Exception('at least one sport is needed', E_ERROR);
//        }
//        return $leastNrOfBatchesNeeded;
//    }

//    protected function getNrOfBatchesNeeded(
//        PouleStructure $pouleStructure,
//        AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport $sport,
//        int $nrOfFields
//    ): int {
//        $nrOfGames = $pouleStructure->calculateTotalNrOfGames([$sport]);
//        return (int)ceil($nrOfGames / $nrOfFields);
//    }
}
