<?php

declare(strict_types=1);

namespace Sports\TestHelper;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsPlanning\Exceptions\NoBestPlanningException;
use SportsPlanning\Planning\PlanningFilter;
use SportsPlanning\Planning\PlanningType;
use SportsPlanning\Planning\TimeoutState;
use SportsPlanning\PlanningOrchestration;
use SportsPlanning\PlanningWithMeta;
use SportsPlanning\Planning;
use SportsScheduler\Game\PlannableGameCreator;
use SportsScheduler\Schedules\CycleCreator;

final class PlanningCreator
{
    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
//        $processor = new UidProcessor();
//        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', Level::Info);
        $logger->pushHandler($handler);
        return $logger;
    }

//    /**
//     * @param PouleStructure $pouleStructure
//     * @param list<SportVariantWithFields> $sportVariantsWithFields
//     * @param RefereeInfo $refereeInfo
//     * @return Input
//     */
//    public function createInput(
//        PouleStructure $pouleStructure,
//        array $sportVariantsWithFields,
//        RefereeInfo $refereeInfo,
//        bool $perPoule = false
//    ) {
////        if ($sportVariantsWithFields === null) {
////            $sportVariantsWithFields = [$this->getAgainstH2hSportVariantWithFields(2)];
////        }
////        if ($refereeInfo === null) {
////            $refereeInfo = new RefereeInfo($this->getDefaultNrOfReferees());
////        }
//        $input = new Input( new Input\Configuration(
//            $pouleStructure,
//            $sportVariantsWithFields,
//            $refereeInfo,
//            $perPoule )
//        );
//
//        return $input;
//    }

//    public function createPlanning(Input $input, SportRange $batchGamesRange = null, int|null $allowedGppMargin = null): Planning
//    {
//        if ($batchGamesRange === null) {
//            $batchGamesRange = new SportRange(1, 1);
//        }
//        $planning = new Planning($input, $batchGamesRange, 0);
//
//        $scheduleCreator = new ScheduleCreator($this->getLogger());
//        if( $allowedGppMargin === null) {
//            $allowedGppMargin = $this->getMaxGppMargin($input->getPoule(1), $this->getLogger());
//        }
//        $schedules = $scheduleCreator->createFromInput($input, $allowedGppMargin);
//        // (new ScheduleOutput($this->getLogger()))->output($schedules);
//        $gameCreator = new GameCreator($this->getLogger());
//        // $gameCreator->disableThrowOnTimeout();
//        $gameCreator->createGames($planning, $schedules);
//
//        $gameAssigner = new GameAssigner($this->getLogger());
//        $gameAssigner->assignGames($planning);
//
//        if (PlanningState::Succeeded !== $planning->getState()) {
//            throw new Exception("planning could not be created", E_ERROR);
//        }
//        return $planning;
//    }

    public function createPlanningWithMeta(
        PlanningOrchestration $orchestration,
        SportRange $nrOfBatchGamesRange = null/*,
        int $maxNrOfGamesInARow = 0,
        bool $disableThrowOnTimeout = false,
        bool $showHighestCompletedBatchNr = false,
        TimeoutState|null $timeoutState = null*/
    ): PlanningWithMeta {
        $maxNrOfGamesInARow = 0;
        $disableThrowOnTimeout = false;
        $showHighestCompletedBatchNr = false;
        $timeoutState = null;

        if ($nrOfBatchGamesRange === null) {
            $nrOfBatchGamesRange = new SportRange(1, 1);
        }
        $planning = Planning::fromConfiguration($orchestration->configuration);
        $planningWithMeta = new PlanningWithMeta($orchestration, $nrOfBatchGamesRange, $maxNrOfGamesInARow, $planning);
//        if ($timeoutState !== null) {
//            $planningWithMeta->setTimeoutState($timeoutState);
//        }

        $cycleCreator = new CycleCreator($this->getLogger());
        $sportRootCyclesMap = $cycleCreator->createSportCyclesMap($orchestration->configuration);

//        foreach( $sportRootCyclesMap as $placeNr => $sportRootCycles) {
//            foreach( $sportRootCycles as $sportRootCycle) {
//                (new ScheduleOutput($this->createLogger()))->outputCycle($sportRootCycle);
//            }
//        }

        $gameCreator = new PlannableGameCreator($this->getLogger());
        $gameCreator->createGamesFromCycles($planning, $sportRootCyclesMap);

        $gameAssigner = new \SportsScheduler\Game\GameAssigner($this->getLogger());
//        if ($disableThrowOnTimeout) {
//            $gameAssigner->disableThrowOnTimeout();
//        }
//        if ($showHighestCompletedBatchNr) {
//            $gameAssigner->showHighestCompletedBatchNr();
//        }
        $betterNrOfBatches = $this->determineBetterNrOfBatches($orchestration, $planningWithMeta->getType(), $nrOfBatchGamesRange);
        if( $betterNrOfBatches === null ) {
            $betterNrOfBatches = $this->calculateMaxNrOfBatches($planningWithMeta);
        }
        if( $gameAssigner->assignGames($planningWithMeta, $betterNrOfBatches) !== \SportsPlanning\Planning\PlanningState::Succeeded ) {
            throw new Exception("planning could not be created", E_ERROR);
        }
        return $planningWithMeta;
    }

    private function calculateMaxNrOfBatches(PlanningWithMeta $planning): int
    {
        $totalNrOfGames = $planning->getConfiguration()->createPlanningPouleStructure()->calculateNrOfGames();
        return (int)ceil($totalNrOfGames / $planning->minNrOfBatchGames);
    }

    public function determineBetterNrOfBatches(
        PlanningOrchestration $orchestration, PlanningType $planningType, SportRange $batchGamesRange): int|null
    {
        try {
            if ($planningType === PlanningType::BatchGames) {
                // -1 because needs to be less nrOfBatches
                return $orchestration->getBestPlanning(null)->getNrOfBatches() - 1;
            } else {
                $planningFilter = new PlanningFilter( null, null, $batchGamesRange, 0);
                $batchGamePlanning = $orchestration->getPlanningWithMeta($planningFilter);
                if ($batchGamePlanning !== null) {
                    return $batchGamePlanning->getNrOfBatches();
                }
            }
        } catch (NoBestPlanningException $e) {
        }
        return null;
    }
}
