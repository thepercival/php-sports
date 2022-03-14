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

    /**
     * @param Structure $structure
     * @param list<Period> $blockedPeriods
     * @param SportRange|null $range
     */
    public function createStructureGames(
        Structure $structure,
        array $blockedPeriods = [],
        SportRange $range = null
    ): void {
        $this->removeGamesHelper($structure->getFirstRoundNumber());
        $this->createGamesHelper($structure->getFirstRoundNumber(), $blockedPeriods, $range);
    }

    /**
     * @param RoundNumber $roundNumber
     * @param list<Period> $blockedPeriods
     * @param SportRange|null $range
     */
    public function createGames(RoundNumber $roundNumber, array $blockedPeriods = [], SportRange $range = null): void
    {
        $this->removeGamesHelper($roundNumber);
        $this->createGamesHelper($roundNumber, $blockedPeriods, $range);
    }

    public function createPlanning(RoundNumber $roundNumber, SportRange $range = null): Planning
    {
        $planningInputCreator = new PlanningInputCreator();
        $nrOfReferees = $roundNumber->getCompetition()->getReferees()->count();
        $planningInput = $planningInputCreator->create($roundNumber, $nrOfReferees);
        $planningCreator = new PlanningCreator();
        return $planningCreator->createPlanning($planningInput, $range);
    }

    /**
     * @param RoundNumber $roundNumber
     * @param list<Period> $blockedPeriods
     * @param SportRange|null $range
     */
    private function createGamesHelper(
        RoundNumber $roundNumber,
        array $blockedPeriods,
        SportRange|null $range
    ): void {
        $minIsMaxPlanning = $this->createPlanning($roundNumber, $range);
        $firstBatch = $minIsMaxPlanning->createFirstBatch();
        if ($firstBatch instanceof SelfRefereeBatchOtherPoule ||
            $firstBatch instanceof SelfRefereeBatchSamePoule) {
            $refereePlaceService = new RefereePlaceService($minIsMaxPlanning);
            $refereePlaceService->assign($firstBatch);
        }

        $planningAssigner = new PlanningAssigner(new PlanningScheduler($blockedPeriods));
        $planningAssigner->assignPlanningToRoundNumber($roundNumber, $minIsMaxPlanning);
        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null) {
            $this->createGamesHelper($nextRoundNumber, $blockedPeriods, $range);
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
