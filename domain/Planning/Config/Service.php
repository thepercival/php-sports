<?php

namespace Sports\Planning\Config;

use Sports\Planning\Config as PlanningConfig;
use SportsPlanning\Input as PlanningInput;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\PouleStructure;
use SportsHelpers\SportConfig as SportConfigHelper;

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
        $config->setNrOfHeadtohead(PlanningConfig::DEFAULTNROFHEADTOHEAD);
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

    public function isTeamupAvailable(RoundNumber $roundNumber): bool
    {
        $sportConfigs = $roundNumber->getSportConfigs();
        foreach ($sportConfigs as $sportConfig) {
            if ($sportConfig->getSport()->getTeam()) {
                return false;
            }
        }
        foreach ($roundNumber->getPoules() as $poule) {
            if ($poule->getPlaces()->count() < PlanningInput::TEAMUP_MIN || $poule->getPlaces()->count(
                ) > PlanningInput::TEAMUP_MAX) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param array|SportConfigHelper[] $portConfigHelpers
     * @return bool
     */
    public function canTeamupBeAvailable(PouleStructure $pouleStructure, array $portConfigHelpers): bool
    {
        if (count($portConfigHelpers) > 1) {
            return false;
        }
        return $pouleStructure->getBiggestPoule() <= PlanningInput::TEAMUP_MAX
            && $pouleStructure->getSmallestPoule() <= PlanningInput::TEAMUP_MIN;
    }

    public function canSelfRefereeBeAvailable(PouleStructure $pouleStructure, int $nrOfGamePlaces): bool
    {
        return $this->canSelfRefereeSamePouleBeAvailable($pouleStructure, $nrOfGamePlaces)
            || $this->canSelfRefereeOtherPoulesBeAvailable($pouleStructure);
    }

    public function canSelfRefereeOtherPoulesBeAvailable(PouleStructure $pouleStructure): bool
    {
        return $pouleStructure->getNrOfPoules() > 1;
    }

    public function canSelfRefereeSamePouleBeAvailable(PouleStructure $pouleStructure, int $nrOfGamePlaces): bool
    {
        return $pouleStructure->getSmallestPoule() > $nrOfGamePlaces;
    }
}
