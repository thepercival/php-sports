<?php

namespace Sports\Round\Number;

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
    /**
     * @var PlanningInputRepository
     */
    protected $inputRepos;
    /**
     * @var PlanningRepository
     */
    protected $planningRepos;
    /**
     * @var RoundNumberRepository
     */
    protected $roundNumberRepos;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        PlanningInputRepository $inputRepos,
        PlanningRepository $planningRepos,
        RoundNumberRepository $roundNumberRepos,
        LoggerInterface $logger = null
    ) {
        $this->inputRepos = $inputRepos;
        $this->planningRepos = $planningRepos;
        $this->roundNumberRepos = $roundNumberRepos;
        $this->logger = $logger;
    }

    public function removeFrom(RoundNumber $roundNumber)
    {
        $this->roundNumberRepos->removePlanning($roundNumber);
        if ($roundNumber->hasNext()) {
            $this->removeFrom($roundNumber->getNext());
        }
    }

    public function addFrom(
        CreatePlanningsEvent $createPlanningEvent,
        RoundNumber $roundNumber,
        Period $blockedPeriod = null
    ) {
        if (!$this->allPreviousRoundNumbersHavePlanning($roundNumber)) {
            return;
        }
        $this->createFrom($createPlanningEvent, $roundNumber, $blockedPeriod);
    }

    public function allPreviousRoundNumbersHavePlanning(RoundNumber $roundNumber): bool
    {
        if ($roundNumber->hasPrevious() === false) {
            return true;
        }
        $previous = $roundNumber->getPrevious();
        if ($previous->getHasPlanning() === false) {
            return false;
        }
        return $this->allPreviousRoundNumbersHavePlanning($previous);
    }

    protected function createFrom(
        CreatePlanningsEvent $createPlanningEvent,
        RoundNumber $roundNumber,
        Period $blockedPeriod = null
    ) {
        $scheduler = new PlanningScheduler($blockedPeriod);
        if ($roundNumber->getHasPlanning()) { // reschedule
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
            if( $planningInput->getPlannings()->filter( function ( Planning $planning ): bool {
                    return $planning->getState() === Planning::STATE_TOBEPROCESSED;
                } )->count() > 0 /* has plannings to be processed,  */) {
                return;
            }
            $planning = $planningInput->getBestPlanning();
            if ($planning === null) {
                if ($this->logger !== null) {
                    $this->logger->info("DEBUG: no best planning found");
                }
                return;
            }
            $planningAssigner = new PlanningAssigner($scheduler);
            $planningAssigner->createGames($roundNumber, $planning);
        }
        $this->roundNumberRepos->savePlanning($roundNumber, true);
        if ($roundNumber->hasNext()) {
            $this->createFrom($createPlanningEvent, $roundNumber->getNext(), $blockedPeriod);
        }
    }
}
