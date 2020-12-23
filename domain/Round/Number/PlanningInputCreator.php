<?php

namespace Sports\Round\Number;

use SportsHelpers\PouleStructure;
use SportsPlanning\Input as PlanningInput;
use Sports\Round\Number as RoundNumber;
use SportsPlanning\Input\Service as PlanningInputService;
use Sports\Planning\Config\Service as PlanningConfigService;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsHelpers\SportConfig;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Planning\Config as PlanningConfig;
use SportsPlanning\Resources;

class PlanningInputCreator
{
    public function __construct()
    {
    }

    public function create(RoundNumber $roundNumber, int $gameMode, int $nrOfReferees): PlanningInput
    {
        $config = $roundNumber->getValidPlanningConfig();
        $planningConfigService = new PlanningConfigService();

        $sportConfigBases = $roundNumber->getCompetitionSports()->map(
            function (CompetitionSport $competitionSport): SportConfig {
                return $competitionSport->createConfig();
            },
        );


        $pouleStructure = $this->createPouleStructure($roundNumber);
        $selfReferee = $this->getSelfReferee(
            $config,
            $sportConfigBases,
            $pouleStructure
        );

        /*
                pas hier gcd toe op poules/aantaldeelnemers(structureconfig), aantal scheidsrechters en aantal velden/sport(sportconfig)
                zorg dat deze functie ook kan worden toegepast vanuit fctoernooi->create_default_planning_input
                dus bijv. [8](8 poules van x deelnemers), 4 refs en [2] kan worden herleid naar een planninginput van [4], 2 refs en [1]

                en bijv. [8,2](8 poules van x aantal deelnemers en 2 poules van y aantal deelnemers ), 4 refs en [2] kan worden herleid naar een planninginput van [4,1], 1 refs en [1]
       */

        $sportConfigs = $this->reduceFields($pouleStructure, $sportConfigBases, $selfReferee );

        return new PlanningInput(
            $pouleStructure,
            $sportConfigs,
            $gameMode,
            $nrOfReferees,
            $selfReferee,
        );
    }

    /**
     * @param PlanningConfig $planningConfig
     * @param array|SportConfig[] $sportConfigs
     * @param PouleStructure $pouleStructure
     * @return int
     */
    protected function getSelfReferee(PlanningConfig $planningConfig, array $sportConfigs, PouleStructure $pouleStructure): int
    {
        $maxNrOfGamePlaces = (new GameCalculator())->getMaxNrOfGamePlaces($sportConfigs, $planningConfig->getTeamup(), false);

        $planningInputService = new PlanningInputService();

        $otherPoulesAvailable = $planningInputService->canSelfRefereeOtherPoulesBeAvailable( $pouleStructure );
        $samePouleAvailable = $planningInputService->canSelfRefereeSamePouleBeAvailable( $pouleStructure, $maxNrOfGamePlaces );
        if (!$otherPoulesAvailable && !$samePouleAvailable) {
            return PlanningInput::SELFREFEREE_DISABLED;
        }
        if ($planningConfig->getSelfReferee() === PlanningInput::SELFREFEREE_OTHERPOULES && !$otherPoulesAvailable) {
            return PlanningInput::SELFREFEREE_SAMEPOULE;
        }
        if ($planningConfig->getSelfReferee() === PlanningInput::SELFREFEREE_SAMEPOULE && !$samePouleAvailable) {
            return PlanningInput::SELFREFEREE_OTHERPOULES;
        }
        return $planningConfig->getSelfReferee();
    }

    protected function createPouleStructure(RoundNumber $roundNumber): PouleStructure
    {
        $nrOfPlacesPerPoule = [];
        foreach ($roundNumber->getPoules() as $poule) {
            $nrOfPlacesPerPoule[] = $poule->getPlaces()->count();
        }
        return new PouleStructure( $nrOfPlacesPerPoule );
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param array|SportConfig[] $sportConfigs
     * @param bool $selfReferee
     * @return array|SportConfig[]
     */
    protected function reduceFields(PouleStructure $pouleStructure, array $sportConfigs, bool $selfReferee): array
    {
        $inputCalculator = new InputCalculator();

        $maxNrOfGamesPerBatch = $inputCalculator->getMaxNrOfGamesPerBatch( $pouleStructure, $sportConfigs, $selfReferee );
        $redcuedConfigs = [];
        foreach( $sportConfigs as $sportConfig ) {
            $reducedNrOfFields = $sportConfig->getNrOfFields();
            if( $reducedNrOfFields > $maxNrOfGamesPerBatch ) {
                $reducedNrOfFields = $maxNrOfGamesPerBatch;
            }
            $redcuedConfigs[] = new SportConfig(
                $sportConfig->getSport(),
                $reducedNrOfFields,
                $sportConfig->getGameAmount() );
        }

        uasort(
            $redcuedConfigs,
            function (SportConfig $sportConfigA, SportConfig $sportConfigB): int {
                return $sportConfigA->getNrOfFields() > $sportConfigB->getNrOfFields() ? -1 : 1;
            }
        );
        return array_values($redcuedConfigs);
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
