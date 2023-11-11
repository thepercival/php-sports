<?php

declare(strict_types=1);

namespace Sports\Round\Number;

use League\Period\Period;
use Psr\Log\LoggerInterface;
use SportsScheduler\Queue\PlanningInput\CreatePlanningsInterface;
use Sports\Round\Number as RoundNumber;
use Sports\Round\Number\PlanningInputCreator as PlanningInputService;
use Sports\Round\Number\Repository as RoundNumberRepository;
use SportsPlanning\Input\Repository as PlanningInputRepository;
use SportsPlanning\Planning\Repository as PlanningRepository;

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

    /**
     * @param CreatePlanningsInterface $createPlanningEvent
     * @param RoundNumber $roundNumber
     * @param list<Period> $blockedPeriods
     * @param int|null $eventPriority
     */
    public function addFrom(
        CreatePlanningsInterface $createPlanningEvent,
        RoundNumber $roundNumber,
        array $blockedPeriods,
        int|null $eventPriority
    ): void {
        if (!$this->allPreviousRoundNumbersHavePlanning($roundNumber)) {
            return;
        }
        $this->createFrom($createPlanningEvent, $roundNumber, $blockedPeriods, $eventPriority);
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

    /**
     * @param CreatePlanningsInterface $createPlannings
     * @param RoundNumber $roundNumber
     * @param list<Period> $blockedPeriods
     * @param int|null $eventPriority
     * @throws \Exception
     */
    protected function createFrom(
        CreatePlanningsInterface $createPlannings,
        RoundNumber $roundNumber,
        array $blockedPeriods,
        int|null $eventPriority
    ): void {
        $scheduler = new PlanningScheduler($blockedPeriods);
        if ($roundNumber->allPoulesHaveGames()) {
            $scheduler->rescheduleGames($roundNumber);
        } else {
            $inputService = new PlanningInputService();
            $nrOfReferees = $roundNumber->getCompetition()->getReferees()->count();
            $defaultPlanningInput = $inputService->create($roundNumber, $nrOfReferees);
            $planningInput = $this->inputRepos->getFromInput($defaultPlanningInput);
            if ($planningInput === null) {
                $this->inputRepos->save($defaultPlanningInput);
//                $this->inputRepos->createBatchGamesPlannings($defaultPlanningInput);
//                if ($this->logger !== null) {
//                    $this->logger->info("DEBUG: send message for roundnumber " . $roundNumber->getNumber());
//                }
                $createPlannings->sendCreatePlannings(
                    $defaultPlanningInput,
                    $roundNumber->getCompetition()->getId(),
                    $roundNumber->getNumber(),
                    $eventPriority
                );
                return;
            }
            if ($planningInput->getSeekingPercentage() < 100) {
                return;
            }
            $planning = $planningInput->getBestPlanning(null);
            $planningAssigner = new PlanningAssigner($scheduler);
            $planningAssigner->assignPlanningToRoundNumber($roundNumber, $planning);
        }
        $this->roundNumberRepos->savePlanning($roundNumber);
        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null) {
            $nextEventPriority = $eventPriority === null ? $eventPriority : $eventPriority - 1;
            $this->createFrom($createPlannings, $nextRoundNumber, $blockedPeriods, $nextEventPriority);
        }
    }
}
