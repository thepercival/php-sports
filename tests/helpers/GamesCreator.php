<?php
declare(strict_types=1);

namespace Sports\TestHelper;

use League\Period\Period;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Sports\Round\Number as RoundNumber;
use Sports\Round\Number\PlanningAssigner;
use Sports\Round\Number\PlanningInputCreator;
use Sports\Round\Number\PlanningScheduler;
use Sports\Structure;
use SportsHelpers\SportRange;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Planning;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;

class GamesCreator
{
    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', Logger::INFO);
        $logger->pushHandler($handler);
        return $logger;
    }

    public function createStructureGames(Structure $structure, Period $blockedPeriod = null, SportRange $range = null): void
    {
        $this->removeGamesHelper($structure->getFirstRoundNumber());
        $this->createGamesHelper($structure->getFirstRoundNumber(), $blockedPeriod, $range);
    }

    public function createGames(RoundNumber $roundNumber, Period $blockedPeriod = null, SportRange $range = null): void
    {
        $this->removeGamesHelper($roundNumber);
        $this->createGamesHelper($roundNumber, $blockedPeriod, $range);
    }

    public function createPlanning(RoundNumber $roundNumber, SportRange $range = null): Planning
    {
        $planningInputCreator = new PlanningInputCreator();
        $nrOfReferees = $roundNumber->getCompetition()->getReferees()->count();
        $planningInput = $planningInputCreator->create($roundNumber, $nrOfReferees);
        $planningCreator = new PlanningCreator();
        return $planningCreator->createPlanning($planningInput, $range);
    }

    private function createGamesHelper(
        RoundNumber $roundNumber,
        Period $blockedPeriod = null,
        SportRange $range = null
    ): void {
        $minIsMaxPlanning = $this->createPlanning($roundNumber, $range);
        $firstBatch = $minIsMaxPlanning->createFirstBatch();
        if ($firstBatch instanceof SelfRefereeBatchOtherPoule ||
            $firstBatch instanceof SelfRefereeBatchSamePoule) {
            $refereePlaceService = new RefereePlaceService($minIsMaxPlanning);
            $refereePlaceService->assign($firstBatch);
        }

        $planningAssigner = new PlanningAssigner(new PlanningScheduler($blockedPeriod));
        $planningAssigner->assignPlanningToRoundNumber($roundNumber, $minIsMaxPlanning);
        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null) {
            $this->createGamesHelper($nextRoundNumber);
        }
    }

    private function removeGamesHelper(RoundNumber $roundNumber): void
    {
        foreach ($roundNumber->getRounds() as $round) {
            foreach ($round->getPoules() as $poule) {
                $poule->getAgainstGames()->clear();
                $poule->getTogetherGames()->clear();
            }
        }
        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null) {
            $this->removeGamesHelper($nextRoundNumber);
        }
    }
}
