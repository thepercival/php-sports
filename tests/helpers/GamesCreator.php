<?php

declare(strict_types=1);

namespace Sports\TestHelper;

use League\Period\Period;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Sports\Round\Number as RoundNumber;
use Sports\Round\Number\PlanningAssigner;
use Sports\Round\Number\PlanningScheduler;
use Sports\Structure;
use SportsHelpers\SelfReferee;
use SportsPlanning\PlanningRefereeInfo;
use SportsHelpers\SportRange;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Planning;
use SportsScheduler\Resource\RefereePlace\Service as RefereePlaceService;

final class GamesCreator
{
    use GppMarginCalculator;

    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', Level::Info);
        $logger->pushHandler($handler);
        return $logger;
    }

    /**
     * @param Structure $structure
     * @param list<Period> $blockedPeriods
     * @param SportRange|null $batchGamesRange
     * @param int|null $allowedGppMargin
     */
    public function createStructureGames(
        Structure $structure,
        array $blockedPeriods = [],
        SportRange|null $batchGamesRange = null,
        int|null $allowedGppMargin = null
    ): void {
        $this->removeGamesHelper($structure->getFirstRoundNumber());
        $this->createGamesHelper($structure->getFirstRoundNumber(), $blockedPeriods, $batchGamesRange, $allowedGppMargin);
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
        SportRange|null $batchGamesRange = null,
        int|null $allowedGppMargin = null): void
    {
        $this->removeGamesHelper($roundNumber);
        $this->createGamesHelper($roundNumber, $blockedPeriods, $batchGamesRange, $allowedGppMargin);
    }

    public function createPlanning(
        RoundNumber $roundNumber,
        PlanningRefereeInfo|null $refereeInfo = null,
        SportRange|null $batchGamesRange = null,
        int|null $allowedGppMargin = null): Planning
    {
        $planningCreator = new PlanningCreator();
        if( $refereeInfo === null ) {
            $refereeInfo = new PlanningRefereeInfo($roundNumber->getCompetition()->getReferees()->count());
        }
        $input = $planningCreator->createInput(
            $roundNumber->createPouleStructure(),
            $roundNumber->getCompetition()->createSportVariantsWithFields(),
            $refereeInfo
        );
        $planningCreator = new PlanningCreator();

        if( $allowedGppMargin === null) {
            $allowedGppMargin = $this->getMaxGppMargin($input->getPoule(1), $this->getLogger());
        }
        return $planningCreator->createPlanning($input, $batchGamesRange, $allowedGppMargin);
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
        SportRange|null $batchGamesRange,
        int|null $allowedGppMargin
    ): void {
        if( $allowedGppMargin === null) {
            $allowedGppMargin = 2; // (new ScheduleCreator($this->getLogger()))->getMaxGppMargin($input, $input->getPoule(1));
        }

        $selfRefereeInfo = $roundNumber->getValidPlanningConfig()->getSelfRefereeInfo();
        if( $selfRefereeInfo->selfReferee === SelfReferee::Disabled ) {
            $refereeInfo = new PlanningRefereeInfo(count($roundNumber->getCompetition()->getReferees()));
        } else {
            $refereeInfo = new PlanningRefereeInfo($selfRefereeInfo);
        }

        $minIsMaxPlanning = $this->createPlanning($roundNumber, $refereeInfo, $batchGamesRange, $allowedGppMargin);
        $firstBatch = $minIsMaxPlanning->createFirstBatch();
        if ($firstBatch instanceof SelfRefereeBatchOtherPoule || $firstBatch instanceof SelfRefereeBatchSamePoule) {
            $refereePlaceService = new RefereePlaceService($minIsMaxPlanning);
            $refereePlaceService->assign($firstBatch);
        }

        $planningAssigner = new PlanningAssigner(new PlanningScheduler($blockedPeriods));
        $planningAssigner->assignPlanningToRoundNumber($roundNumber, $minIsMaxPlanning);
        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null) {
            $this->createGamesHelper($nextRoundNumber, $blockedPeriods, $batchGamesRange, $allowedGppMargin);
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
