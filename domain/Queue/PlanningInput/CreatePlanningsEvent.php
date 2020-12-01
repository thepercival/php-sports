<?php

namespace Sports\Queue\PlanningInput;

use Sports\Competition;
use SportsPlanning\Input as PlanningInput;

interface CreatePlanningsEvent
{
    public function sendCreatePlannings(PlanningInput $input, Competition $competition = null, int $startRoundNumber = null);
}
