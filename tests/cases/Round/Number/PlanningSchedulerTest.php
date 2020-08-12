<?php

namespace Sports\Tests\Round\Number;

use League\Period\Period;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Round\Number\PlanningAssigner;
use Sports\Round\Number\PlanningScheduler;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\PlanningCreator as PlanningCreatorHelper;
use Sports\Planning\Config\Service as PlanningConfigService;
use Sports\Structure\Service as StructureService;
use Sports\TestHelper\PlanningReplacer;
use Sports\Game;
use \Exception;

class PlanningSchedulerTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, PlanningCreatorHelper, PlanningReplacer;

    public function testValidDateTimes()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6, 2);

        $structureService->addQualifiers($structure->getRootRound(), QualifyGroup::WINNERS, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();

        $options = [];
        $firstRoundNumberPlanning = $this->createPlanning($firstRoundNumber, $options);
        $secondRoundNumberPlanning = $this->createPlanning($secondRoundNumber, $options);

        $planningAssigner = new PlanningAssigner(new PlanningScheduler());
        $planningAssigner->createGames($firstRoundNumber, $firstRoundNumberPlanning);
        $planningAssigner->createGames($secondRoundNumber, $secondRoundNumberPlanning);

//        foreach( $firstRoundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
//            (new \SportsPlanning\Output\Game())->output($game);
//        }

        $competitionStartDateTime = $competition->getStartDateTime();

        $planningScheduler = new PlanningScheduler();
        $planningScheduler->rescheduleGames($firstRoundNumber);

        self::assertEquals($competitionStartDateTime, $firstRoundNumber->getGames()[0]->getStartDateTime());
        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames()[0]->getStartDateTime());
    }

    public function testBlockedPeriodBeforeFirstGame()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6, 2);

        $structureService->addQualifiers($structure->getRootRound(), QualifyGroup::WINNERS, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();

        $options = [];
        $firstRoundNumberPlanning = $this->createPlanning($firstRoundNumber, $options);
        $secondRoundNumberPlanning = $this->createPlanning($secondRoundNumber, $options);

        $planningAssigner = new PlanningAssigner(new PlanningScheduler());
        $planningAssigner->createGames($firstRoundNumber, $firstRoundNumberPlanning);
        $planningAssigner->createGames($secondRoundNumber, $secondRoundNumberPlanning);

//        foreach( $firstRoundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
//            (new \SportsPlanning\Output\Game())->output($game);
//        }

        $competitionStartDateTime = $competition->getStartDateTime();

        $blockedPeriod = new Period(
            $competitionStartDateTime->modify("-1 minutes"),
            $competitionStartDateTime->modify("+" . (40 - 1) . " minutes")
        );
        $planningScheduler = new PlanningScheduler($blockedPeriod);
        $planningScheduler->rescheduleGames($firstRoundNumber);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40 - 1);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames()[0]->getStartDateTime());
    }

    public function testBlockedPeriodBeforeSecondBatchGame()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6, 2);

        $structureService->addQualifiers($structure->getRootRound(), QualifyGroup::WINNERS, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();

        $options = [];
        $firstRoundNumberPlanning = $this->createPlanning($firstRoundNumber, $options);
        $secondRoundNumberPlanning = $this->createPlanning($secondRoundNumber, $options);

        $planningAssigner = new PlanningAssigner(new PlanningScheduler());
        $planningAssigner->createGames($firstRoundNumber, $firstRoundNumberPlanning);
        $planningAssigner->createGames($secondRoundNumber, $secondRoundNumberPlanning);

//        foreach( $firstRoundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
//            (new \SportsPlanning\Output\Game())->output($game);
//        }

        $competitionStartDateTime = $competition->getStartDateTime();

        $secondBatchGame = $firstRoundNumber->getGames(Game::ORDER_BY_BATCH)[2];

        $blockedPeriod = new Period(
            $secondBatchGame->getStartDateTime()->modify("-1 minutes"),
            $secondBatchGame->getStartDateTime()->modify("+40 minutes")
        );
        $planningScheduler = new PlanningScheduler($blockedPeriod);
        $planningScheduler->rescheduleGames($firstRoundNumber);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames()[0]->getStartDateTime());
    }

    public function testBlockedPeriodDuringSecondBatchGame()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6, 2);

        $structureService->addQualifiers($structure->getRootRound(), QualifyGroup::WINNERS, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();

        $options = [];
        $firstRoundNumberPlanning = $this->createPlanning($firstRoundNumber, $options);
        $secondRoundNumberPlanning = $this->createPlanning($secondRoundNumber, $options);

        $planningAssigner = new PlanningAssigner(new PlanningScheduler());
        $planningAssigner->createGames($firstRoundNumber, $firstRoundNumberPlanning);
        $planningAssigner->createGames($secondRoundNumber, $secondRoundNumberPlanning);

//        foreach( $firstRoundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
//            (new \SportsPlanning\Output\Game())->output($game);
//        }

        $competitionStartDateTime = $competition->getStartDateTime();

        $secondBatchGame = $firstRoundNumber->getGames(Game::ORDER_BY_BATCH)[2];

        $blockedPeriod = new Period(
            $secondBatchGame->getStartDateTime()->modify("+1 minutes"),
            $secondBatchGame->getStartDateTime()->modify("+40 minutes")
        );
        $planningScheduler = new PlanningScheduler($blockedPeriod);
        $planningScheduler->rescheduleGames($firstRoundNumber);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames()[0]->getStartDateTime());
    }

    public function testBlockedPeriodAtStartSecondBatchGame()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6, 2);

        $structureService->addQualifiers($structure->getRootRound(), QualifyGroup::WINNERS, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();

        $options = [];
        $firstRoundNumberPlanning = $this->createPlanning($firstRoundNumber, $options);
        $secondRoundNumberPlanning = $this->createPlanning($secondRoundNumber, $options);

        $planningAssigner = new PlanningAssigner(new PlanningScheduler());
        $planningAssigner->createGames($firstRoundNumber, $firstRoundNumberPlanning);
        $planningAssigner->createGames($secondRoundNumber, $secondRoundNumberPlanning);

//        foreach( $firstRoundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
//            (new \SportsPlanning\Output\Game())->output($game);
//        }

        $competitionStartDateTime = $competition->getStartDateTime();

        $secondBatchGame = $firstRoundNumber->getGames(Game::ORDER_BY_BATCH)[2];

        $blockedPeriod = new Period(
            clone $secondBatchGame->getStartDateTime(),
            $secondBatchGame->getStartDateTime()->modify("+40 minutes")
        );
        $planningScheduler = new PlanningScheduler($blockedPeriod);
        $planningScheduler->rescheduleGames($firstRoundNumber);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames()[0]->getStartDateTime());
    }

    public function testBlockedPeriodBetweenRounds()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6, 2);

        $structureService->addQualifiers($structure->getRootRound(), QualifyGroup::WINNERS, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();

        $options = [];
        $firstRoundNumberPlanning = $this->createPlanning($firstRoundNumber, $options);
        $secondRoundNumberPlanning = $this->createPlanning($secondRoundNumber, $options);

        $planningAssigner = new PlanningAssigner(new PlanningScheduler());
        $planningAssigner->createGames($firstRoundNumber, $firstRoundNumberPlanning);
        $planningAssigner->createGames($secondRoundNumber, $secondRoundNumberPlanning);

//        foreach( $firstRoundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
//            (new \SportsPlanning\Output\Game())->output($game);
//        }

        $competitionStartDateTime = $competition->getStartDateTime();

        $secondRoundNumberStartDateTimeTmp = $this->getStartSecond($competitionStartDateTime);

        $blockedPeriod = new Period(
            $secondRoundNumberStartDateTimeTmp->modify("-1 minutes"),
            $secondRoundNumberStartDateTimeTmp->modify("+40 minutes")
        );
        $planningScheduler = new PlanningScheduler($blockedPeriod);
        $planningScheduler->rescheduleGames($firstRoundNumber);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames()[0]->getStartDateTime());
    }

    public function testRoundNumberNoGames()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6, 2);

        $structureService->addQualifiers($structure->getRootRound(), QualifyGroup::WINNERS, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();

        $options = [];
        $firstRoundNumberPlanning = $this->createPlanning($firstRoundNumber, $options);
        $secondRoundNumberPlanning = $this->createPlanning($secondRoundNumber, $options);

        $planningAssigner = new PlanningAssigner(new PlanningScheduler());
        $planningAssigner->createGames($firstRoundNumber, $firstRoundNumberPlanning);
        $planningAssigner->createGames($secondRoundNumber, $secondRoundNumberPlanning);

        $secondRoundNumber->getPoules()[0]->getGames()->clear();
//        foreach( $firstRoundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
//            (new \SportsPlanning\Output\Game())->output($game);
//        }

        $planningScheduler = new PlanningScheduler();
        self::expectException(Exception::class);
        $planningScheduler->rescheduleGames($firstRoundNumber);
    }

    protected function getStartSecond(\DateTimeImmutable $startFirst, int $delta = 0): \DateTimeImmutable
    {
        $planningConfigService = new PlanningConfigService();
        $addMinutes = 3 * $planningConfigService->getDefaultMinutesPerGame();
        $addMinutes += 2 * $planningConfigService->getDefaultMinutesBetweenGames();
        $addMinutes += $planningConfigService->getDefaultMinutesAfter();
        return $startFirst->modify("+" . ($addMinutes + $delta) . " minutes");
    }
}
