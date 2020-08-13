<?php

namespace Sports\TestHelper;

use League\Period\Period;
use Sports\Round\Number\PlanningAssigner;
use Sports\Round\Number\PlanningScheduler;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use Sports\Structure;
use SportsPlanning\Planning;
use Sports\Round\Number as RoundNumber;
use Sports\Round\Number\PlanningInputCreator;
use SportsPlanning\Service as PlanningService;

trait GamesCreator {

    protected function createGames(Structure $structure, Period $blockedPeriod = null)
    {
        $this->removeGamesHelper($structure->getFirstRoundNumber());
        $this->createGamesHelper($structure->getFirstRoundNumber(), $blockedPeriod);
    }

    private function createGamesHelper(RoundNumber $roundNumber, Period $blockedPeriod = null)
    {
        // make trait to do job below!!
        $planningInputCreator = new PlanningInputCreator();
        $nrOfReferees = $roundNumber->getCompetition()->getReferees()->count();
        $planningInput = $planningInputCreator->create($roundNumber, $nrOfReferees);
        $planningService = new PlanningService();
        $minIsMaxPlanning = $planningService->createNextMinIsMaxPlanning($planningInput);
        $state = $planningService->createGames($minIsMaxPlanning);
        if ($state !== Planning::STATE_SUCCESS) {
            //throw assertuib
        }

        if ($roundNumber->getValidPlanningConfig()->selfRefereeEnabled()) {
            $refereePlaceService = new RefereePlaceService($minIsMaxPlanning);
            $refereePlaceService->assign($minIsMaxPlanning->createFirstBatch());
        }

        $convertService = new PlanningAssigner(new PlanningScheduler($blockedPeriod));
        $convertService->createGames($roundNumber, $minIsMaxPlanning);

        if ($roundNumber->hasNext()) {
            $this->createGamesHelper($roundNumber->getNext());
        }
    }

    private function removeGamesHelper( RoundNumber $roundNumber )
    {
        foreach($roundNumber->getRounds() as $round ) {
            foreach($round->getPoules() as $poule ) {
                $poule->getGames()->clear();
            }
        }
        if( $roundNumber->hasNext() ) {
            $this->removeGamesHelper( $roundNumber->getNext() );
        }
    }
}

