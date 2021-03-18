<?php

namespace Sports\Round\Number;

use SportsHelpers\PouleStructure;
use SportsPlanning\Input as PlanningInput;
use Sports\Round\Number as RoundNumber;
use SportsPlanning\Input\Service as PlanningInputService;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsHelpers\SportConfig;
use Sports\Planning\Config as PlanningConfig;
use SportsPlanning\SelfReferee;

class PlanningInputCreator
{
    public function __construct()
    {
    }

    public function create(RoundNumber $roundNumber, int $nrOfReferees): PlanningInput
    {
        $config = $roundNumber->getValidPlanningConfig();

        $sportConfigBases = $roundNumber->createSportConfigs();
        $pouleStructure = $this->createPouleStructure($roundNumber);
        $selfReferee = $this->getSelfReferee(
            $config,
            $sportConfigBases,
            $pouleStructure
        );
        $sportConfigs = $this->reduceFields($pouleStructure, $sportConfigBases, $selfReferee !== SelfReferee::DISABLED);
        return new PlanningInput(
            $pouleStructure,
            $sportConfigs,
            $nrOfReferees,
            $selfReferee,
        );
    }

    /**
     * @param PlanningConfig $planningConfig
     * @param list<SportConfig> $sportConfigs
     * @param PouleStructure $pouleStructure
     * @return int
     */
    protected function getSelfReferee(PlanningConfig $planningConfig, array $sportConfigs, PouleStructure $pouleStructure): int
    {
        $planningInputService = new PlanningInputService();

        $otherPoulesAvailable = $planningInputService->canSelfRefereeOtherPoulesBeAvailable($pouleStructure);
        $samePouleAvailable = $planningInputService->canSelfRefereeSamePouleBeAvailable($pouleStructure, $sportConfigs);
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
        return new PouleStructure($nrOfPlacesPerPoule);
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportConfig> $sportConfigs
     * @param bool $selfReferee
     * @return list<SportConfig>
     */
    protected function reduceFields(PouleStructure $pouleStructure, array $sportConfigs, bool $selfReferee): array
    {
        $inputCalculator = new InputCalculator();
        $maxNrOfGamesPerBatch = $inputCalculator->getMaxNrOfGamesPerBatch($pouleStructure, $sportConfigs, $selfReferee);
        $reducedConfigs = [];
        foreach ($sportConfigs as $sportConfig) {
            $reducedNrOfFields = $sportConfig->getNrOfFields();
            if ($reducedNrOfFields > $maxNrOfGamesPerBatch) {
                $reducedNrOfFields = $maxNrOfGamesPerBatch;
            }
            $reducedConfigs[] = new SportConfig(
                $sportConfig->getGameMode(),
                $sportConfig->getNrOfGamePlaces(),
                $reducedNrOfFields,
                $sportConfig->getGameAmount()
            );
        }

        uasort(
            $reducedConfigs,
            function (SportConfig $sportConfigA, SportConfig $sportConfigB): int {
                return $sportConfigA->getNrOfFields() > $sportConfigB->getNrOfFields() ? -1 : 1;
            }
        );
        return array_values($reducedConfigs);
    }

//    public function getSufficientNrOfHeadtoheadByRoundNumber(RoundNumber $roundNumber, array $sportConfig): int
//    {
//        $config = $roundNumber->getValidPlanningConfig();
//        $poule = $this->getSmallestPoule($roundNumber);
//        $pouleNrOfPlaces = $poule->getPlaces()->count();
//        return $this->getSufficientNrOfHeadtohead(
//            $config->getNrOfHeadtohead(),
//            $pouleNrOfPlaces,
//            $config->getTeamup(),
//            $config->getSelfReferee(),
//            $sportConfig
//        );
//    }

//    public function getSufficientNrOfHeadtohead(
//        int $defaultNrOfHeadtohead,
//        int $pouleNrOfPlaces,
//        bool $teamup,
//        bool $selfReferee,
//        array $sportConfig
//    ): int {
//        $sportService = new SportService();
//        $nrOfHeadtohead = $defaultNrOfHeadtohead;
//        //    $nrOfHeadtohead = $roundNumber->getValidPlanningConfig()->getNrOfHeadtohead();
//        //        sporten zijn nu planningsporten, maar voor de berekening heb ik alleen een array
//        //        zodra de berekening is gedaan hoef je daarna bij het bepalen van het aantal games
//        //        niet meer te kijken als je het aantal velden kan verkleinen!
//        $sportsNrFields = $this->convertSportConfig($sportConfig);
//        $sportsNrFieldsGames = $sportService->getPlanningMinNrOfGames(
//            $sportsNrFields,
//            $pouleNrOfPlaces,
//            $teamup,
//            $selfReferee,
//            $nrOfHeadtohead
//        );
//        $nrOfPouleGamesBySports = $sportService->getNrOfPouleGamesBySports(
//            $pouleNrOfPlaces,
//            $sportsNrFieldsGames,
//            $teamup,
//            $selfReferee
//        );
//        while (($sportService->getNrOfPouleGames(
//                $pouleNrOfPlaces,
//                $teamup,
//                $nrOfHeadtohead
//            )) < $nrOfPouleGamesBySports) {
//            $nrOfHeadtohead++;
//        }
//        if (($sportService->getNrOfPouleGames(
//                $pouleNrOfPlaces,
//                $teamup,
//                $nrOfHeadtohead
//            )) === $nrOfPouleGamesBySports) {
//            $nrOfGamePlaces = array_sum(
//                array_map(
//                    function (SportNrFields $sportNrFields) {
//                        return $sportNrFields->getNrOfFields() * $sportNrFields->getNrOfGamePlaces();
//                    },
//                    $sportsNrFields
//                )
//            );
//            if (($nrOfGamePlaces % $pouleNrOfPlaces) !== 0
//                && ($pouleNrOfPlaces % 2) !== 0  /* $pouleNrOfPlaces 1 van beide niet deelbaar door 2 */) {
//                $nrOfHeadtohead++;
//            }
//        }
//
//        if ($nrOfHeadtohead < $defaultNrOfHeadtohead) {
//            return $defaultNrOfHeadtohead;
//        }
//        return $nrOfHeadtohead;
//    }
//
//    protected function getSmallestPoule(RoundNumber $roundNumber): Poule
//    {
//        $smallestPoule = null;
//        foreach ($roundNumber->getPoules() as $poule) {
//            if ($smallestPoule === null || $poule->getPlaces()->count() < $smallestPoule->getPlaces()->count()) {
//                $smallestPoule = $poule;
//            }
//        }
//        return $smallestPoule;
//    }
//
//    /**
//     * @param array $sportsConfigs
//     * @return array|SportNrFields[]
//     */
//    protected function convertSportConfig(array $sportsConfigs): array
//    {
//        $sportNr = 1;
//        return array_map(
//            function ($sportConfig) use (&$sportNr) {
//                return new SportNrFields($sportNr++, $sportConfig["nrOfFields"], $sportConfig["nrOfGamePlaces"]);
//            },
//            $sportsConfigs
//        );
//    }
}
