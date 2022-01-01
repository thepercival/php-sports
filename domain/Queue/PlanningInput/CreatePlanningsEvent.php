<?php

declare(strict_types=1);

namespace Sports\Queue\PlanningInput;

use Sports\Competition;
use SportsPlanning\Input as PlanningInput;

interface CreatePlanningsEvent
{
    public function sendCreatePlannings(
        PlanningInput $input,
        Competition $competition = null,
        int $startRoundNumber = null,
        int|null $priority = null
    ): void;
}
