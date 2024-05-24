<?php

declare(strict_types=1);

namespace Sports\Round\Number;

use Sports\Planning\GameAmountConfig;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\PouleStructure;
use SportsPlanning\Input\ConfigurationValidator;
use SportsPlanning\PouleStructure as PlanningPouleStructure;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsPlanning\Input\Configuration as InputConfiguration;
use SportsPlanning\Referee\Info as RefereeInfo;

class InputConfigurationCreator
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
     * @return list<SportVariantWithFields>
     */
    protected function createSportVariantsWithFields(RoundNumber $roundNumber): array
    {
        $gameAmountConfigs = $roundNumber->getValidGameAmountConfigs();
        return array_map(function (GameAmountConfig $gameAmountConfig): SportVariantWithFields {
            return new SportVariantWithFields(
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
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @param RefereeInfo $refereeInfo
     * @return list<SportVariantWithFields>
     */
    protected function reduceFields(
        PouleStructure $pouleStructure,
        array $sportVariantsWithFields,
        RefereeInfo $refereeInfo
    ): array {
        $planningPouleStructure = new PlanningPouleStructure(
            $pouleStructure,
            $sportVariantsWithFields,
            $refereeInfo
        );
        $maxNrOfGamesPerBatch = $planningPouleStructure->getMaxNrOfGamesPerBatch();
        $reducedSportVariants = [];
        foreach ($sportVariantsWithFields as $sportVariantWithField) {
            $reducedNrOfFields = $sportVariantWithField->getNrOfFields();
            if ($reducedNrOfFields > $maxNrOfGamesPerBatch) {
                $reducedNrOfFields = $maxNrOfGamesPerBatch;
            }
            $reducedSportVariants[] = new SportVariantWithFields(
                $sportVariantWithField->getSportVariant(),
                $reducedNrOfFields
            );
        }

        $moreReducedSportVariants = $this->reduceFieldsBySports($pouleStructure, $reducedSportVariants);

        usort(
            $moreReducedSportVariants,
            function (SportVariantWithFields $sportA, SportVariantWithFields $sportB): int {
                return $sportA->getNrOfFields() > $sportB->getNrOfFields() ? -1 : 1;
            }
        );
        return $moreReducedSportVariants;
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @return list<SportVariantWithFields>
     */
    protected function reduceFieldsBySports(PouleStructure $pouleStructure, array $sportVariantsWithFields): array
    {
        $leastNrOfBatchesNeeded = $this->getLeastNrOfBatchesNeeded($pouleStructure, $sportVariantsWithFields);
        return array_map(
            function (SportVariantWithFields $sportVariantWithFields) use (
                $pouleStructure,
                $leastNrOfBatchesNeeded
            ): SportVariantWithFields {
                return $this->reduceSportVariantFields(
                    $pouleStructure,
                    $sportVariantWithFields,
                    $leastNrOfBatchesNeeded
                );
            },
            $sportVariantsWithFields
        );
    }

    protected function reduceSportVariantFields(
        PouleStructure $pouleStructure,
        SportVariantWithFields $sportVariantWithFields,
        int $minNrOfBatches
    ): SportVariantWithFields {
        $sportVariant = $sportVariantWithFields->getSportVariant();
        $nrOfFields = $sportVariantWithFields->getNrOfFields();
        if ($nrOfFields === 1) {
            return $sportVariantWithFields;
        }
        $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded($pouleStructure, $sportVariant, $nrOfFields);
        while ($nrOfBatchesNeeded < $minNrOfBatches) {
            if (--$nrOfFields === 1) {
                break;
            }
            $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded($pouleStructure, $sportVariant, $nrOfFields);
        }
        return new SportVariantWithFields($sportVariant, $nrOfFields);
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @return int
     */
    protected function getLeastNrOfBatchesNeeded(PouleStructure $pouleStructure, array $sportVariantsWithFields): int
    {
        $leastNrOfBatchesNeeded = null;
        foreach ($sportVariantsWithFields as $sportVariantWithField) {
            $nrOfBatchesNeeded = $this->getNrOfBatchesNeeded(
                $pouleStructure,
                $sportVariantWithField->getSportVariant(),
                $sportVariantWithField->getNrOfFields()
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
        AgainstH2h|AgainstGpp|Single|AllInOneGame $sportVariant,
        int $nrOfFields
    ): int {
        $nrOfGames = $pouleStructure->getTotalNrOfGames([$sportVariant]);
        return (int)ceil($nrOfGames / $nrOfFields);
    }
}
