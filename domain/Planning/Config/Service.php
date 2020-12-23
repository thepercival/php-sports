<?php

namespace Sports\Planning\Config;

use Sports\Planning\Config as PlanningConfig;
use SportsPlanning\Input as PlanningInput;
use Sports\Round\Number as RoundNumber;

class Service
{
    public function createDefault(RoundNumber $roundNumber): PlanningConfig
    {
        $config = new PlanningConfig($roundNumber);
        $config->setExtension(PlanningConfig::DEFAULTEXTENSION);
        $config->setEnableTime(PlanningConfig::DEFAULTENABLETIME);
        $config->setMinutesPerGame(0);
        $config->setMinutesPerGameExt(0);
        $config->setMinutesBetweenGames(0);
        $config->setMinutesAfter(0);
        $config->setMinutesPerGame($this->getDefaultMinutesPerGame());
        $config->setMinutesBetweenGames($this->getDefaultMinutesBetweenGames());
        $config->setMinutesAfter($this->getDefaultMinutesAfter());
        $config->setTeamup(false);
        $config->setSelfReferee(PlanningInput::SELFREFEREE_DISABLED);
        $config->setNrOfHeadtohead(PlanningConfig::DEFAULTGAMEAMOUNT);
        return $config;
    }

    public function copy(PlanningConfig $planningConfig, RoundNumber $roundNumber)
    {
        $newPlanningConfig = new PlanningConfig($roundNumber);

        $newPlanningConfig->setExtension($planningConfig->getExtension());
        $newPlanningConfig->setEnableTime($planningConfig->getEnableTime());
        $newPlanningConfig->setMinutesPerGame($planningConfig->getMinutesPerGame());
        $newPlanningConfig->setMinutesPerGameExt($planningConfig->getMinutesPerGameExt());
        $newPlanningConfig->setMinutesBetweenGames($planningConfig->getMinutesBetweenGames());
        $newPlanningConfig->setMinutesAfter($planningConfig->getMinutesAfter());
        $newPlanningConfig->setEnableTime($planningConfig->getEnableTime());
        $newPlanningConfig->setMinutesPerGame($planningConfig->getMinutesPerGame());
        $newPlanningConfig->setMinutesBetweenGames($planningConfig->getMinutesBetweenGames());
        $newPlanningConfig->setMinutesAfter($planningConfig->getMinutesAfter());
        $newPlanningConfig->setTeamup($planningConfig->getTeamup());
        $newPlanningConfig->setSelfReferee($planningConfig->getSelfReferee());
        $newPlanningConfig->setNrOfHeadtohead($planningConfig->getNrOfHeadtohead());
    }

    public function getDefaultMinutesPerGame(): int
    {
        return 20;
    }

    public function getDefaultMinutesPerGameExt(): int
    {
        return 5;
    }

    public function getDefaultMinutesBetweenGames(): int
    {
        return 5;
    }

    public function getDefaultMinutesAfter(): int
    {
        return 5;
    }

    public function isAgainstEachOtherAvailable(RoundNumber $roundNumber): bool
    {
        $sportConfigs = $roundNumber->getCompetitionSports();
        foreach ($sportConfigs as $sportConfig) {
            if ($sportConfig->getSport()->getTeam()) {
                return false;
            }
        }
        foreach ($roundNumber->getPoules() as $poule) {
            if ($poule->getPlaces()->count() > PlanningInput::AGAINSTEACHOTHER_MAXNROFGAMEPLACES) {
                return false;
            }
        }
        return true;
    }
}
