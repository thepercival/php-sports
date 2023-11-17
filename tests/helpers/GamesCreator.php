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
use Sports\Round\Number\PlanningScheduler;
use Sports\Structure;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsHelpers\SportRange;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Planning;
use SportsScheduler\Resource\RefereePlace\Service as RefereePlaceService;
use SportsScheduler\Schedule\Creator as ScheduleCreator;

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
     * @param int|null $allowedGppMargin
     */
    public function createStructureGames(
        Structure $structure,
        array $blockedPeriods = [],
        SportRange $range = null,
        int|null $allowedGppMargin = null
    ): void {
        $this->removeGamesHelper($structure->getFirstRoundNumber());
        $this->createGamesHelper($structure->getFirstRoundNumber(), $blockedPeriods, $range, $allowedGppMargin);
    }

    /**
     * @param RoundNumber $roundNumber
     * @param list<Period> $blockedPeriods
     * @param SportRange|null $range
     * @param int|null $allowedGppMargin
     */
    public function createGames(
        RoundNumber $roundNumber,
        array $blockedPeriods = [],
        SportRange $range = null,
        int|null $allowedGppMargin = null): void
    {
        $this->removeGamesHelper($roundNumber);
        $this->createGamesHelper($roundNumber, $blockedPeriods, $range, $allowedGppMargin);
    }

    public function createPlanning(RoundNumber $roundNumber, SportRange $range = null, int|null $allowedGppMargin = null): Planning
    {

        $planningCreator = new PlanningCreator();
        $nrOfReferees = $roundNumber->getCompetition()->getReferees()->count();
        $input = $planningCreator->createInput(
            $roundNumber->createPouleStructure(),
            $roundNumber->getCompetition()->createSportVariantsWithFields(),
            new RefereeInfo($nrOfReferees)
        );
        $planningCreator = new PlanningCreator();

        if( $allowedGppMargin === null) {
            $allowedGppMargin = (new ScheduleCreator($this->getLogger()))->getMaxGppMargin($input, $input->getPoule(1));
        }
        return $planningCreator->createPlanning($input, $range, $allowedGppMargin);
    }

    /**
     * @param RoundNumber $roundNumber
     * @param list<Period> $blockedPeriods
     * @param SportRange|null $range
     * @param int|null $allowedGppMargin
     */
    private function createGamesHelper(
        RoundNumber $roundNumber,
        array $blockedPeriods,
        SportRange|null $range,
        int|null $allowedGppMargin
    ): void {
        if( $allowedGppMargin === null) {
            $allowedGppMargin = 2; // (new ScheduleCreator($this->getLogger()))->getMaxGppMargin($input, $input->getPoule(1));
        }
        $minIsMaxPlanning = $this->createPlanning($roundNumber, $range, $allowedGppMargin);
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
            $this->createGamesHelper($nextRoundNumber, $blockedPeriods, $range, $allowedGppMargin);
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
