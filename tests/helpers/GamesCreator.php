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
use SportsHelpers\RefereeInfo;
use SportsHelpers\SportRange;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\PlanningOrchestration;
use SportsPlanning\PlanningWithMeta;
use SportsScheduler\Resource\RefereePlaces\RefereePlaceService;

final class GamesCreator
{

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
     */
    public function createStructureGames(
        Structure $structure,
        array $blockedPeriods = [],
        SportRange $batchGamesRange = null
    ): void {
        $this->removeGamesHelper($structure->getFirstRoundNumber());
        $this->createGamesHelper($structure->getFirstRoundNumber(), $blockedPeriods, $batchGamesRange);
    }

    /**
     * @param RoundNumber $roundNumber
     * @param list<Period> $blockedPeriods
     * @param SportRange|null $range
     */
    public function createGames(
        RoundNumber $roundNumber,
        array $blockedPeriods = [],
        SportRange $batchGamesRange = null): void
    {
        $this->removeGamesHelper($roundNumber);
        $this->createGamesHelper($roundNumber, $blockedPeriods, $batchGamesRange);
    }

    public function createPlanningWithMeta(
        RoundNumber $roundNumber,
        RefereeInfo|null $refereeInfo = null,
        SportRange $batchGamesRange = null): PlanningWithMeta
    {
        $nrOfReferees = $roundNumber->getCompetition()->getReferees()->count();
        if( $refereeInfo === null && $nrOfReferees > 0 ) {
            $refereeInfo = RefereeInfo::fromNrOfReferees($nrOfReferees);
        }

        $planningConfiguration = new PlanningConfiguration(
            $roundNumber->createPouleStructure(),
            $roundNumber->createSportWithNrOfFieldsAndNrOfCycles(),
            $refereeInfo,
            false
        );

        $planningOrchestration = new PlanningOrchestration($planningConfiguration);
        $planningCreator = new PlanningCreator();
        return $planningCreator->createPlanningWithMeta($planningOrchestration, $batchGamesRange);
    }

    /**
     * @param RoundNumber $roundNumber
     * @param list<Period> $blockedPeriods
     * @param SportRange|null $range
     */
    private function createGamesHelper(
        RoundNumber $roundNumber,
        array $blockedPeriods,
        SportRange|null $batchGamesRange
    ): void {
        $refereeInfo = $roundNumber->getRefereeInfo();

        $planningWithMeta = $this->createPlanningWithMeta($roundNumber, $refereeInfo, $batchGamesRange);
        $firstBatch = $planningWithMeta->createFirstBatch();
        if ($firstBatch instanceof SelfRefereeBatchOtherPoules ||
            $firstBatch instanceof SelfRefereeBatchSamePoule) {
            $refereePlaceService = new RefereePlaceService($planningWithMeta);
            $refereePlaceService->assign($firstBatch);
        }

        $planningAssigner = new PlanningAssigner(new PlanningScheduler($blockedPeriods));
        $planningAssigner->assignPlanningToRoundNumber($roundNumber, $planningWithMeta);
        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null) {
            $this->createGamesHelper($nextRoundNumber, $blockedPeriods, $batchGamesRange);
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
