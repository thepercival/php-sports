<?php

namespace Sports\TestHelper;

use SportsPlanning\Planning;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use SportsPlanning\Service as PlanningService;
use Sports\Round\Number\PlanningInputCreator;
use Sports\Round\Number as RoundNumber;

trait PlanningCreator {
    protected function createPlanning( RoundNumber $roundNumber, array $options ): Planning
    {
        $planningInputCreator = new PlanningInputCreator();
        $nrOfReferees = $roundNumber->getCompetition()->getReferees()->count();
        $planningInput = $planningInputCreator->create($roundNumber, $nrOfReferees);
        $planningService = new PlanningService();
        $planning = $planningService->createNextMinIsMaxPlanning($planningInput);
        if (Planning::STATE_SUCCESS !== $planningService->createGames($planning)) {
            throw new \Exception("planning could not be created", E_ERROR);
        }
        if ($roundNumber->getValidPlanningConfig()->selfRefereeEnabled()) {
            $refereePlaceService = new RefereePlaceService($planning);
            $refereePlaceService->assign($planning->createFirstBatch());
        }
        return $planning;
    }
}

