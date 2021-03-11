<?php

namespace Sports\TestHelper;

use League\Period\Period;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Sports\Round\Number\PlanningAssigner;
use Sports\Round\Number\PlanningScheduler;
use SportsHelpers\Range;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use Sports\Structure;
use Sports\Round\Number as RoundNumber;
use Sports\Round\Number\PlanningInputCreator;

class GamesCreator
{
    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', LOG_INFO);
        $logger->pushHandler($handler);
        return $logger;
    }

    public function createStructureGames(Structure $structure, Period $blockedPeriod = null, Range $range = null)
    {
        $this->removeGamesHelper($structure->getFirstRoundNumber());
        $this->createGamesHelper($structure->getFirstRoundNumber(), $blockedPeriod, $range);
    }

    public function createGames(RoundNumber $roundNumber, Period $blockedPeriod = null, Range $range = null)
    {
        $this->removeGamesHelper($roundNumber);
        $this->createGamesHelper($roundNumber, $blockedPeriod, $range);
    }

    private function createGamesHelper(RoundNumber $roundNumber, Period $blockedPeriod = null, Range $range = null)
    {
        // make trait to do job below!!
        $planningInputCreator = new PlanningInputCreator();
        $nrOfReferees = $roundNumber->getCompetition()->getReferees()->count();
        $planningInput = $planningInputCreator->create($roundNumber, $nrOfReferees);
        $planningCreator = new PlanningCreator();
        $minIsMaxPlanning = $planningCreator->createPlanning($planningInput, $range);

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

    private function removeGamesHelper(RoundNumber $roundNumber)
    {
        foreach ($roundNumber->getRounds() as $round) {
            foreach ($round->getPoules() as $poule) {
                $poule->getAgainstGames()->clear();
                $poule->getTogetherGames()->clear();
            }
        }
        if ($roundNumber->hasNext()) {
            $this->removeGamesHelper($roundNumber->getNext());
        }
    }
}
