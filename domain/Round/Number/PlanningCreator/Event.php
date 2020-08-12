<?php

namespace Sports\Round\Number\PlanningCreator;

use Sports\Competition;
use SportsPlanning\Input as PlanningInput;

interface Event
{
    public function sendCreatePlannings(PlanningInput $input, Competition $competition = null, int $startRoundNumber = null);
}
