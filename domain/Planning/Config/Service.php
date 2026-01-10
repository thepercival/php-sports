<?php

declare(strict_types=1);

namespace Sports\Planning\Config;

use Sports\Planning\Config as PlanningConfig;
use Sports\Planning\EditMode;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\SelfReferee;

final class Service
{
    public function createDefault(RoundNumber $roundNumber): PlanningConfig
    {
        return new PlanningConfig(
            $roundNumber,
            EditMode::Auto,
            PlanningConfig::DEFAULTEXTENSION,
            PlanningConfig::DEFAULTENABLETIME,
            $this->getDefaultMinutesPerGame(),
            $this->getDefaultMinutesPerGameExt(),
            $this->getDefaultMinutesBetweenGames(),
            $this->getDefaultMinutesAfter(),
            false,
            SelfReferee::Disabled,
            0,
            false
        );
    }

    public function copy(PlanningConfig $fromPlanningConfig, RoundNumber $toRoundNumber): PlanningConfig
    {
        return new PlanningConfig(
            $toRoundNumber,
            $fromPlanningConfig->getEditMode(),
            $fromPlanningConfig->getExtension(),
            $fromPlanningConfig->getEnableTime(),
            $fromPlanningConfig->getMinutesPerGame(),
            $fromPlanningConfig->getMinutesPerGameExt(),
            $fromPlanningConfig->getMinutesBetweenGames(),
            $fromPlanningConfig->getMinutesAfter(),
            $fromPlanningConfig->getPerPoule(),
            $fromPlanningConfig->getSelfReferee(),
            $fromPlanningConfig->getNrOfSimSelfRefs(),
            $fromPlanningConfig->getBestLast()
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
