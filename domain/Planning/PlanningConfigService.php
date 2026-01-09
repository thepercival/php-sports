<?php

declare(strict_types=1);

namespace Sports\Planning;

use Sports\Planning\PlanningConfig as PlanningConfig;
use Sports\Round\Number as RoundNumber;

final class PlanningConfigService
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
            null,
            null,
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
            $fromPlanningConfig->getSelfRefereeInfo()?->selfReferee,
            $fromPlanningConfig->getSelfRefereeInfo()?->nrOfSimSelfRefs,
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
