<?php

namespace Sports\Round\Number;

use Sports\Game\Order as GameOrder;
use Sports\Round\Number\PlanningInputCreator as PlanningInputService;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Repository as PlanningRepository;
use SportsPlanning\Input\Repository as PlanningInputRepository;
use Sports\Round\Number as RoundNumber;
use Sports\Round\Number\Repository as RoundNumberRepository;
use League\Period\Period;
use Sports\Queue\PlanningInput\CreatePlanningsEvent;
use Psr\Log\LoggerInterface;

class PlanningCreator
{
    protected LoggerInterface|null $logger;

    public function __construct(
        protected PlanningInputRepository $inputRepos,
        protected PlanningRepository $planningRepos,
        protected RoundNumberRepository $roundNumberRepos,
        LoggerInterface|null $logger = null
    ) {
        $this->logger = $logger;
    }

    public function removeFrom(RoundNumber $roundNumber): void
    {
        $this->roundNumberRepos->removePlanning($roundNumber);
        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null) {
            $this->removeFrom($nextRoundNumber);
        }
    }

    public function addFrom(
        CreatePlanningsEvent $createPlanningEvent,
        RoundNumber $roundNumber,
        Period $blockedPeriod = null
    ): void {
        if (!$this->allPreviousRoundNumbersHavePlanning($roundNumber)) {
            return;
        }
        $this->createFrom($createPlanningEvent, $roundNumber, $blockedPeriod);
    }

    public function allPreviousRoundNumbersHavePlanning(RoundNumber $roundNumber): bool
    {
        $previous = $roundNumber->getPrevious();
        if ($previous === null) {
            return true;
        }
        if (!$previous->allPoulesHaveGames()) {
            return false;
        }
        return $this->allPreviousRoundNumbersHavePlanning($previous);
    }

    protected function createFrom(
        CreatePlanningsEvent $createPlanningEvent,
        RoundNumber $roundNumber,
        Period $blockedPeriod = null
    ): void {
        $scheduler = new PlanningScheduler($blockedPeriod);
        if ($roundNumber->allPoulesHaveGames()) {
            $scheduler->rescheduleGames($roundNumber);
        } else {
            $inputService = new PlanningInputService();
            $nrOfReferees = $roundNumber->getCompetition()->getReferees()->count();
            $defaultPlanningInput = $inputService->create($roundNumber, $nrOfReferees);
            $planningInput = $this->inputRepos->getFromInput($defaultPlanningInput);
            if ($planningInput === null) {
                $this->inputRepos->save($defaultPlanningInput);
                $this->inputRepos->createBatchGamesPlannings($defaultPlanningInput);
                if ($this->logger !== null) {
                    $this->logger->info("DEBUG: send message for roundnumber " . $roundNumber->getNumber());
                }
                $createPlanningEvent->sendCreatePlannings(
                    $defaultPlanningInput,
                    $roundNumber->getCompetition(),
                    $roundNumber->getNumber()
                );
                return;
            }
            if ($planningInput->getPlannings()->filter(function (Planning $planning): bool {
                return $planning->getState() === Planning::STATE_TOBEPROCESSED;
            })->count() > 0 /* has plannings to be processed,  */) {
                return;
            }
            $planning = $planningInput->getBestPlanning();
            $planningAssigner = new PlanningAssigner($scheduler);
            $planningAssigner->assignPlanningToRoundNumber($roundNumber, $planning);
        }
        $this->roundNumberRepos->savePlanning($roundNumber);
        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null) {
            $this->createFrom($createPlanningEvent, $nextRoundNumber, $blockedPeriod);
        }
    }
}
