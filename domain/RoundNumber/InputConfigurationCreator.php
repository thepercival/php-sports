<?php

declare(strict_types=1);

namespace Sports\Round\Number;

use Sports\Planning\GameAmountConfig;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SportVariants\AgainstOneVsOne;
use SportsHelpers\SportVariants\AgainstOneVsTwo;
use SportsHelpers\SportVariants\AgainstTwoVsTwo;
use SportsHelpers\SportVariants\AllInOneGame;
use SportsHelpers\SportVariants\Persist\SportPersistVariantWithNrOfFields;
use SportsHelpers\SportVariants\Single;
use SportsPlanning\Input\ConfigurationValidator;
use SportsPlanning\PlanningPouleStructure;
use SportsPlanning\Input\Configuration as InputConfiguration;
use SportsPlanning\Referee\Info as RefereeInfo;

final class InputConfigurationCreator
{
    public function __construct()
    {
    }

    public function create(RoundNumber $roundNumber, RefereeInfo $refereeInfo): InputConfiguration
    {
        $config = $roundNumber->getValidPlanningConfig();

        $pouleStructure = $this->createPouleStructure($roundNumber);
        $sportVariantsWithFields = $this->createSportVariantsWithFields($roundNumber);

        $configurationValidator = new ConfigurationValidator();
        return $configurationValidator->createReducedAndValidatedInputConfiguration(
            $pouleStructure,
            $sportVariantsWithFields,
            $refereeInfo,
            $config->getPerPoule()
        );
    }

    /**
     * @return list<SportPersistVariantWithNrOfFields>
     */
    protected function createSportVariantsWithFields(RoundNumber $roundNumber): array
    {
        $gameAmountConfigs = $roundNumber->getValidGameAmountConfigs();
        return array_map(function (GameAmountConfig $gameAmountConfig): SportPersistVariantWithNrOfFields {
            return new SportPersistVariantWithNrOfFields(
                $gameAmountConfig->createVariant(),
                $gameAmountConfig->getCompetitionSport()->getFields()->count()
            );
        }, $gameAmountConfigs);
    }


    protected function createPouleStructure(RoundNumber $roundNumber): PouleStructure
    {
        $nrOfPlacesPerPoule = [];
        foreach ($roundNumber->getPoules() as $poule) {
            $nrOfPlacesPerPoule[] = $poule->getPlaces()->count();
        }
        return new PouleStructure(...$nrOfPlacesPerPoule);
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportPersistVariantWithNrOfFields> $sportPersistVariantsWithFields
     * @param RefereeInfo $refereeInfo
     * @return list<SportPersistVariantWithNrOfFields>
     */
    protected function reduceFields(
        PouleStructure $pouleStructure,
        array $sportPersistVariantsWithFields,
        RefereeInfo $refereeInfo
    ): array {
        $planningPouleStructure = new PlanningPouleStructure(
            $pouleStructure,
            $sportPersistVariantsWithFields,
            $refereeInfo
        );
        $maxNrOfGamesPerBatch = $planningPouleStructure->getMaxNrOfGamesPerBatch();
        $reducedSportVariants = [];
        foreach ($sportPersistVariantsWithFields as $sportVariantWithField) {
            $reducedNrOfFields = $sportVariantWithField->nrOfFields;
            if ($reducedNrOfFields > $maxNrOfGamesPerBatch) {
                $reducedNrOfFields = $maxNrOfGamesPerBatch;
            }
            $reducedSportVariants[] = new SportPersistVariantWithNrOfFields(
                $sportVariantWithField->createSportVariant(),
                $reducedNrOfFields
            );
        }

        $moreReducedSportVariants = $this->reduceFieldsBySports($pouleStructure, $reducedSportVariants);

        usort(
            $moreReducedSportVariants,
            function (SportPersistVariantWithNrOfFields $sportA, SportPersistVariantWithNrOfFields $sportB): int {
                return $sportA->nrOfFields > $sportB->nrOfFields ? -1 : 1;
            }
        );
        return $moreReducedSportVariants;
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportPersistVariantWithNrOfFields> $sportPersistVariantsWithFields
     * @return list<SportPersistVariantWithNrOfFields>
     */
    protected function reduceFieldsBySports(PouleStructure $pouleStructure, array $sportPersistVariantsWithFields): array
    {
        $leastNrOfBatchesNeeded = $this->getLeastNrOfBatchesNeeded($pouleStructure, $sportPersistVariantsWithFields);
        return array_map(
            function (SportPersistVariantWithNrOfFields $sportPersistVariantWithFields) use (
                $pouleStructure,
                $leastNrOfBatchesNeeded
            ): SportPersistVariantWithNrOfFields {
                return $this->reduceSportVariantFields(
                    $pouleStructure,
                    $sportPersistVariantWithFields,
                    $leastNrOfBatchesNeeded
                );
            },
            $sportPersistVariantsWithFields
        );
    }

    protected function reduceSportVariantFields(
        PouleStructure $pouleStructure,
        SportPersistVariantWithNrOfFields $sportPersistVariantWithFields,
        int $minNrOfBatches
    ): SportPersistVariantWithNrOfFields {
        $sportVariant = $sportPersistVariantWithFields->createSportVariant();
        $nrOfFields = $sportPersistVariantWithFields->nrOfFields;
        if ($nrOfFields === 1) {
            return $sportPersistVariantWithFields;
        }
        $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded($pouleStructure, $sportVariant, $nrOfFields);
        while ($nrOfBatchesNeeded < $minNrOfBatches) {
            if (--$nrOfFields === 1) {
                break;
            }
            $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded($pouleStructure, $sportVariant, $nrOfFields);
        }
        return new SportPersistVariantWithNrOfFields($sportVariant, $nrOfFields);
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportPersistVariantWithNrOfFields> $sportVariantsWithFields
     * @return int
     */
    protected function getLeastNrOfBatchesNeeded(PouleStructure $pouleStructure, array $sportVariantsWithFields): int
    {
        $leastNrOfBatchesNeeded = null;
        foreach ($sportVariantsWithFields as $sportVariantWithField) {
            $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded(
                $pouleStructure,
                $sportVariantWithField->createSportVariant(),
                $sportVariantWithField->nrOfFields
            );
            if ($leastNrOfBatchesNeeded === null || $nrOfBatchesNeeded > $leastNrOfBatchesNeeded) {
                $leastNrOfBatchesNeeded = $nrOfBatchesNeeded;
            }
        }
        if ($leastNrOfBatchesNeeded === null) {
            throw new \Exception('at least one sport is needed', E_ERROR);
        }
        return $leastNrOfBatchesNeeded;
    }

    protected function getNrOfBatchesNeeded(
        PouleStructure $pouleStructure,
        AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|Single|AllInOneGame $sportVariant,
        int $nrOfFields
    ): int {
        $nrOfGames = $pouleStructure->calculateTotalNrOfGames([$sportVariant]);
        return (int)ceil($nrOfGames / $nrOfFields);
    }
}
