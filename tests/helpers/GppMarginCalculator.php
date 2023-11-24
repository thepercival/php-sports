<?php

declare(strict_types = 1);

namespace Sports\TestHelper;

use Psr\Log\LoggerInterface;
use SportsPlanning\Poule as PlanningPoule;
use SportsScheduler\Schedule\Creator as ScheduleCreator;

trait GppMarginCalculator
{
    protected function getMaxGppMargin(PlanningPoule $planningPoule, LoggerInterface $logger): int
    {
        $sports = array_values($planningPoule->getInput()->getSports()->toArray());

        $scheduleCreator = new ScheduleCreator($logger);
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sports);
        $nrOfPlaces = count($planningPoule->getPlaces());
        return $scheduleCreator->getMaxGppMargin($sportVariantsWithNr, $nrOfPlaces);
    }
}