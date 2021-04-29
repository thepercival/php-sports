<?php
declare(strict_types=1);

namespace Sports\Planning\Config;

use Sports\Competition;
use Sports\Planning\Config as PlanningConfig;
use SportsHelpers\GameMode;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\SelfReferee;

class Service
{
    public function createDefault(RoundNumber $roundNumber): PlanningConfig
    {
        return new PlanningConfig(
            $roundNumber,
            PlanningConfig::DEFAULTEXTENSION,
            PlanningConfig::DEFAULTENABLETIME,
            $this->getDefaultMinutesPerGame(),
            $this->getDefaultMinutesPerGameExt(),
            $this->getDefaultMinutesBetweenGames(),
            $this->getDefaultMinutesAfter(),
            SelfReferee::DISABLED
        );
    }

    public function copy(PlanningConfig $planningConfig, RoundNumber $roundNumber): PlanningConfig
    {
        return new PlanningConfig(
            $roundNumber,
            $planningConfig->getExtension(),
            $planningConfig->getEnableTime(),
            $planningConfig->getMinutesPerGame(),
            $planningConfig->getMinutesPerGameExt(),
            $planningConfig->getMinutesBetweenGames(),
            $planningConfig->getMinutesAfter(),
            $planningConfig->getSelfReferee(),
        );
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
}
