<?php
declare(strict_types=1);

namespace Sports\Planning\Config;

use Sports\Competition;
use Sports\Game\CreationStrategy;
use Sports\Planning\Config as PlanningConfig;
use SportsHelpers\GameMode;
use Sports\Round\Number as RoundNumber;
use SportsPlanning\SelfReferee;

class Service
{
    public function createDefault(RoundNumber $roundNumber): PlanningConfig
    {
        $config = new PlanningConfig($roundNumber);
        $gameCreationStrategy = $this->getDefaultGameCreationStrategy($roundNumber->getCompetition());
        $config->setCreationStrategy($gameCreationStrategy);
        $config->setExtension(PlanningConfig::DEFAULTEXTENSION);
        $config->setEnableTime(PlanningConfig::DEFAULTENABLETIME);
        $config->setMinutesPerGame(0);
        $config->setMinutesPerGameExt(0);
        $config->setMinutesBetweenGames(0);
        $config->setMinutesAfter(0);
        $config->setMinutesPerGame($this->getDefaultMinutesPerGame());
        $config->setMinutesBetweenGames($this->getDefaultMinutesBetweenGames());
        $config->setMinutesAfter($this->getDefaultMinutesAfter());
        $config->setSelfReferee(SelfReferee::DISABLED);

        return $config;
    }

    public function copy(PlanningConfig $planningConfig, RoundNumber $roundNumber): void
    {
        $newPlanningConfig = new PlanningConfig($roundNumber);

        $newPlanningConfig->setCreationStrategy($planningConfig->getCreationStrategy());
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
        $newPlanningConfig->setSelfReferee($planningConfig->getSelfReferee());
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

    public function getDefaultGameCreationStrategy(Competition $competition): int
    {
        $competitionSports = $competition->getSports();
        if ($competitionSports->count() > 1) {
            return CreationStrategy::StaticManual;
        }
        $sport = $competition->getSingleSport();
        if ($sport->getSport()->getGameMode() === GameMode::AGAINST) {
            return CreationStrategy::StaticPouleSize;
        }
        if ($sport->getSport()->getNrOfGamePlaces() > 2) {
            return CreationStrategy::IncrementalRandom;
        }
        return CreationStrategy::StaticManual;
    }
}
