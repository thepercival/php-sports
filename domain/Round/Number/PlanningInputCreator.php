<?php
declare(strict_types=1);

namespace Sports\Round\Number;

use SportsHelpers\GameMode;
use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\Variant as SportVariant;
use SportsPlanning\Input as PlanningInput;
use Sports\Round\Number as RoundNumber;
use Sports\Planning\GameAmountConfig;
use SportsPlanning\Input\Service as PlanningInputService;
use SportsPlanning\Input\Calculator as InputCalculator;
use Sports\Planning\Config as PlanningConfig;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;

class PlanningInputCreator
{
    public function __construct()
    {
    }

    public function create(RoundNumber $roundNumber, int $nrOfReferees): PlanningInput
    {
        $config = $roundNumber->getValidPlanningConfig();

        $sportVariantsWithFields = $this->createSportVariantsWithFields($roundNumber);
        $pouleStructure = $this->createPouleStructure($roundNumber);
        $selfReferee = $this->getSelfReferee(
            $config,
            $roundNumber->createSportVariants(),
            $pouleStructure
        );
        $efficientSportVariants = $this->reduceFields($pouleStructure, $sportVariantsWithFields, $nrOfReferees, $selfReferee !== SelfReferee::DISABLED);
        return new PlanningInput(
            $pouleStructure,
            $efficientSportVariants,
            $config->getGamePlaceStrategy(),
            $nrOfReferees,
            $selfReferee,
        );
    }

    /**
     * @return list<SportVariantWithFields>
     */
    protected function createSportVariantsWithFields(RoundNumber $roundNumber): array
    {
        $gameAmountConfigs = $roundNumber->getValidGameAmountConfigs();
        return array_values(array_map(function (GameAmountConfig $gameAmountConfig): SportVariantWithFields {
            return new SportVariantWithFields(
                $gameAmountConfig->createVariant(),
                $gameAmountConfig->getCompetitionSport()->getFields()->count()
            );
        }, $gameAmountConfigs));
    }

    /**
     * @param PlanningConfig $planningConfig
     * @param list<SportVariant> $sportVariants
     * @param PouleStructure $pouleStructure
     * @return int
     */
    protected function getSelfReferee(PlanningConfig $planningConfig, array $sportVariants, PouleStructure $pouleStructure): int
    {
        $planningInputService = new PlanningInputService();

        $otherPoulesAvailable = $planningInputService->canSelfRefereeOtherPoulesBeAvailable($pouleStructure);
        $samePouleAvailable = $planningInputService->canSelfRefereeSamePouleBeAvailable($pouleStructure, $sportVariants);
        if (!$otherPoulesAvailable && !$samePouleAvailable) {
            return SelfReferee::DISABLED;
        }
        if ($planningConfig->getSelfReferee() === SelfReferee::OTHERPOULES && !$otherPoulesAvailable) {
            return SelfReferee::SAMEPOULE;
        }
        if ($planningConfig->getSelfReferee() === SelfReferee::SAMEPOULE && !$samePouleAvailable) {
            return SelfReferee::OTHERPOULES;
        }
        return $planningConfig->getSelfReferee();
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
     * @param bool $selfReferee
     * @param int $nrOfReferees
     * @return list<SportVariantWithFields>
     */
    protected function reduceFields(PouleStructure $pouleStructure, array $sportVariantsWithFields, int $nrOfReferees, bool $selfReferee): array
    {
        $inputCalculator = new InputCalculator();
        $maxNrOfGamesPerBatch = $inputCalculator->getMaxNrOfGamesPerBatch($pouleStructure, $sportVariantsWithFields, $nrOfReferees, $selfReferee);
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

        usort(
            $reducedSportVariants,
            function (SportVariantWithFields $sportA, SportVariantWithFields $sportB): int {
                return $sportA->getNrOfFields() > $sportB->getNrOfFields() ? -1 : 1;
            }
        );
        return $reducedSportVariants;
    }
}
