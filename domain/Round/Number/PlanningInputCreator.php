<?php
declare(strict_types=1);

namespace Sports\Round\Number;

use Sports\Planning\Config as PlanningConfig;
use Sports\Planning\GameAmountConfig;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsPlanning\Input as PlanningInput;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsPlanning\Input\Service as PlanningInputService;

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
        if( $selfReferee !== SelfReferee::Disabled ) {
            $nrOfReferees = 0;
        }
        $efficientSportVariants = $this->reduceFields($pouleStructure, $sportVariantsWithFields, $nrOfReferees, $selfReferee !== SelfReferee::Disabled);
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
        return array_map(function (GameAmountConfig $gameAmountConfig): SportVariantWithFields {
            return new SportVariantWithFields(
                $gameAmountConfig->createVariant(),
                $gameAmountConfig->getCompetitionSport()->getFields()->count()
            );
        }, $gameAmountConfigs);
    }

    /**
     * @param PlanningConfig $planningConfig
     * @param list<AgainstSportVariant|SingleSportVariant|AllInOneGameSportVariant> $sportVariants
     * @param PouleStructure $pouleStructure
     * @return SelfReferee
     */
    protected function getSelfReferee(PlanningConfig $planningConfig, array $sportVariants, PouleStructure $pouleStructure): SelfReferee
    {
        $planningInputService = new PlanningInputService();

        $otherPoulesAvailable = $planningInputService->canSelfRefereeOtherPoulesBeAvailable($pouleStructure);
        $samePouleAvailable = $planningInputService->canSelfRefereeSamePouleBeAvailable($pouleStructure, $sportVariants);
        if (!$otherPoulesAvailable && !$samePouleAvailable) {
            return SelfReferee::Disabled;
        }
        if ($planningConfig->getSelfReferee() === SelfReferee::OtherPoules && !$otherPoulesAvailable) {
            return SelfReferee::SamePoule;
        }
        if ($planningConfig->getSelfReferee() === SelfReferee::SamePoule && !$samePouleAvailable) {
            return SelfReferee::OtherPoules;
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
