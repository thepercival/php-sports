<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Sports\Planning\Input;

use SportsPlanning\HelperTmp;
use SportsPlanning\Input as PlanningInput;
use Sports\Round\Number as RoundNumber;
use Sports\Planning\Config\Service as PlanningConfigService;
use SportsPlanning\Sport\NrFields as SportNrFields;
use Sports\Sport\Service as SportService;
use Sports\Sport\Config\Service as SportConfigService;
use Sports\Sport\Config as SportConfig;
use SportsHelpers\SportConfig as SportConfigHelper;
use Sports\Planning\Config as PlanningConfig;

class Service
{
    public function __construct()
    {
    }

    public function get(RoundNumber $roundNumber, int $nrOfReferees): PlanningInput
    {
        $config = $roundNumber->getValidPlanningConfig();
        $planningConfigService = new PlanningConfigService();
        $teamup = $config->getTeamup() ? $planningConfigService->isTeamupAvailable($roundNumber) : $config->getTeamup();

        $sportConfigBases = array_map(
            function (SportConfig $sportConfig): SportConfigHelper {
                return $sportConfig->createHelper();
            },
            $roundNumber->getSportConfigs()
        );
        $selfReferee = $this->getSelfReferee(
            $config,
            $sportConfigBases,
            $roundNumber->getNrOfPlaces(),
            count($roundNumber->getPoules())
        );
        $nrOfReferees = $selfReferee === PlanningInput::SELFREFEREE_DISABLED ? $nrOfReferees : 0;
        /*
                pas hier gcd toe op poules/aantaldeelnemers(structureconfig), aantal scheidsrechters en aantal velden/sport(sportconfig)
                zorg dat deze functie ook kan worden toegepast vanuit fctoernooi->create_default_planning_input
                dus bijv. [8](8 poules van x deelnemers), 4 refs en [2] kan worden herleid naar een planninginput van [4], 2 refs en [1]

                en bijv. [8,2](8 poules van x aantal deelnemers en 2 poules van y aantal deelnemers ), 4 refs en [2] kan worden herleid naar een planninginput van [4,1], 1 refs en [1]


        */
        $nrOfHeadtohead = $config->getNrOfHeadtohead();
        $structureConfig = $this->getStructureConfig($roundNumber);
        $sportConfig = $this->getSportConfig($roundNumber, $nrOfHeadtohead, $teamup);

        // $multipleSports = count($sportConfig) > 1;
//        if ($multipleSports) {
//            $nrOfHeadtohead = $this->getSufficientNrOfHeadtoheadByRoundNumber($roundNumber, $sportConfig);
//        }
        return new PlanningInput(
            $structureConfig,
            $sportConfig,
            $nrOfReferees,
            $teamup,
            $selfReferee,
            $nrOfHeadtohead
        );
    }

    /**
     * @param PlanningConfig $planningConfig
     * @param array|SportConfigHelper[] $sportConfigHelpers
     * @param int $nrOfPlaces
     * @param int $nrOfPoules
     * @return int
     */
    protected function getSelfReferee(PlanningConfig $planningConfig, array $sportConfigHelpers, int $nrOfPlaces, int $nrOfPoules): int
    {
        $maxNrOfGamePlaces = (new HelperTmp())->getMaxNrOfGamePlaces($sportConfigHelpers, $planningConfig->getTeamup(), false);

        $planningConfigService = new PlanningConfigService();

        $otherPoulesAvailable = $planningConfigService->canSelfRefereeOtherPoulesBeAvailable($nrOfPoules);
        $samePouleAvailable = $planningConfigService->canSelfRefereeSamePouleBeAvailable(
            $nrOfPoules,
            $nrOfPlaces,
            $maxNrOfGamePlaces
        );
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

    public function getStructureConfig(RoundNumber $roundNumber): array
    {
        $nrOfPlacesPerPoule = [];
        foreach ($roundNumber->getPoules() as $poule) {
            $nrOfPlacesPerPoule[] = $poule->getPlaces()->count();
        }
        uasort(
            $nrOfPlacesPerPoule,
            function (int $nrOfPlacesA, int $nrOfPlacesB) {
                return $nrOfPlacesA > $nrOfPlacesB ? -1 : 1;
            }
        );
        return array_values($nrOfPlacesPerPoule);
    }

    /**
     * @param RoundNumber $roundNumber
     * @param int $nrOfHeadtohead
     * @param bool $teamup
     * @return array
     */
    protected function getSportConfig(RoundNumber $roundNumber, int $nrOfHeadtohead, bool $teamup): array
    {
        $maxNrOfFields = $this->getMaxNrOfFields($roundNumber, $nrOfHeadtohead, $teamup);

        $sportConfigRet = [];
        /** @var SportConfig $sportConfig */
        foreach ($roundNumber->getSportConfigs() as $sportConfig) {
            $nrOfFields = $sportConfig->getFields()->count();
            if ($nrOfFields > $maxNrOfFields) {
                $nrOfFields = $maxNrOfFields;
            }
            $sportConfigRet[] = [
                "nrOfFields" => $nrOfFields,
                "nrOfGamePlaces" => $sportConfig->getNrOfGamePlaces()
            ];
        }
        uasort(
            $sportConfigRet,
            function (array $sportA, array $sportB) {
                return $sportA["nrOfFields"] > $sportB["nrOfFields"] ? -1 : 1;
            }
        );
        return array_values($sportConfigRet);
    }

    protected function getMaxNrOfFields(RoundNumber $roundNumber, int $nrOfHeadtohead, bool $teamup): int
    {
        $sportService = new SportService();
        $nrOfGames = 0;
        /** @var \Sports\Poule $poule */
        foreach ($roundNumber->getPoules() as $poule) {
            $nrOfGames += (new HelperTmp())->getNrOfGamesPerPoule($poule->getPlaces()->count(), $teamup, $nrOfHeadtohead);
        }
        return $nrOfGames;
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
