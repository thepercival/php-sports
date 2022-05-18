<?php

declare(strict_types=1);

namespace Sports\Tests\Round\Number;

use Sports\Qualify\Target as QualifyTarget;
use DateTimeImmutable;
use League\Period\Period;
use PHPUnit\Framework\TestCase;
use Sports\Game;
use Sports\Round\Number\PlanningScheduler;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\Planning\Config\Service as PlanningConfigService;
use Sports\Game\Order as GameOrder;
use Exception;
use Sports\TestHelper\StructureEditorCreator;
use SportsHelpers\SportRange;

final class PlanningSchedulerTest extends TestCase
{
    use CompetitionCreator;
    use StructureEditorCreator;

    public function testValidDateTimes(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3]);
        $rootRound = $structure->getRootRound();
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();
        self::assertNotNull($secondRoundNumber);

        (new GamesCreator())->createStructureGames($structure, [], new SportRange(2, 2));

        $competitionStartDateTime = $competition->getStartDateTime();

        $planningScheduler = new PlanningScheduler([]);
        $planningScheduler->rescheduleGames($firstRoundNumber);

        $firstRoundNumberGames = $firstRoundNumber->getGames(GameOrder::ByBatch);
        $firstRoundNumberGame = array_shift($firstRoundNumberGames);
        self::assertNotNull($firstRoundNumberGame);
        self::assertEquals($competitionStartDateTime, $firstRoundNumberGame->getStartDateTime());
        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime);
        $secondRoundNumberGames = $secondRoundNumber->getGames(GameOrder::ByBatch);
        $secondRoundNumberGame = array_shift($secondRoundNumberGames);
        self::assertNotNull($secondRoundNumberGame);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumberGame->getStartDateTime());
    }

    public function testBlockedPeriodBeforeFirstGame(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3]);

        $rootRound = $structure->getRootRound();
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();
        self::assertNotNull($secondRoundNumber);

        (new GamesCreator())->createStructureGames($structure, [], new SportRange(2, 2));

//        foreach( $firstRoundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
//            (new AgainstGameOutput())->output($game);
//        }
//        foreach( $secondRoundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
//            (new AgainstGameOutput())->output($game);
//        }
//        echo PHP_EOL;

        $competitionStartDateTime = $competition->getStartDateTime();

        $blockedPeriod = new Period(
            $competitionStartDateTime->modify("-1 minutes"),
            $competitionStartDateTime->modify("+" . (40 - 1) . " minutes")
        );
        $planningScheduler = new PlanningScheduler([$blockedPeriod]);
        $planningScheduler->rescheduleGames($firstRoundNumber);

//        foreach( $firstRoundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
//            (new AgainstGameOutput())->output($game);
//        }
//        foreach( $secondRoundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
//            (new AgainstGameOutput())->output($game);
//        }

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40 - 1);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames(GameOrder::ByBatch)[0]->getStartDateTime());
    }

    public function testBlockedPeriodBeforeSecondBatchGame(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3]);

        $rootRound = $structure->getRootRound();
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();
        self::assertNotNull($secondRoundNumber);

        (new GamesCreator())->createStructureGames($structure, [], new SportRange(2, 2));

//        foreach( $firstRoundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
//            (new \SportsPlanning\Output\Game())->output($game);
//        }

        $competitionStartDateTime = $competition->getStartDateTime();

        $secondBatchGame = $firstRoundNumber->getGames(GameOrder::ByBatch)[2];

        $blockedPeriod = new Period(
            $secondBatchGame->getStartDateTime()->modify("-1 minutes"),
            $secondBatchGame->getStartDateTime()->modify("+40 minutes")
        );
        $planningScheduler = new PlanningScheduler([$blockedPeriod]);
        $planningScheduler->rescheduleGames($firstRoundNumber);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames(GameOrder::ByPoule)[0]->getStartDateTime());
    }

    public function testBlockedPeriodDuringSecondBatchGame(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3]);

        $rootRound = $structure->getRootRound();
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();
        self::assertNotNull($secondRoundNumber);

        (new GamesCreator())->createStructureGames($structure, [], new SportRange(2, 2));

//        foreach( $firstRoundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
//            (new \SportsPlanning\Output\Game())->output($game);
//        }

        $competitionStartDateTime = $competition->getStartDateTime();

        $secondBatchGame = $firstRoundNumber->getGames(GameOrder::ByBatch)[2];

        $blockedPeriod = new Period(
            $secondBatchGame->getStartDateTime()->modify("+1 minutes"),
            $secondBatchGame->getStartDateTime()->modify("+40 minutes")
        );
        $planningScheduler = new PlanningScheduler([$blockedPeriod]);
        $planningScheduler->rescheduleGames($firstRoundNumber);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames(GameOrder::ByPoule)[0]->getStartDateTime());
    }

    public function testBlockedPeriodAtStartSecondBatchGame(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3]);

        $rootRound = $structure->getRootRound();
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();
        self::assertNotNull($secondRoundNumber);

        (new GamesCreator())->createStructureGames($structure, [], new SportRange(2, 2));

//        foreach( $firstRoundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
//            (new \SportsPlanning\Output\Game())->output($game);
//        }

        $competitionStartDateTime = $competition->getStartDateTime();

        $secondBatchGame = $firstRoundNumber->getGames(GameOrder::ByBatch)[2];

        $blockedPeriod = new Period(
            clone $secondBatchGame->getStartDateTime(),
            $secondBatchGame->getStartDateTime()->modify("+40 minutes")
        );
        $planningScheduler = new PlanningScheduler([$blockedPeriod]);
        $planningScheduler->rescheduleGames($firstRoundNumber);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames(GameOrder::ByPoule)[0]->getStartDateTime());
    }

    public function testBlockedPeriodBetweenRounds(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3]);

        $rootRound = $structure->getRootRound();
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();
        self::assertNotNull($secondRoundNumber);

        (new GamesCreator())->createStructureGames($structure, [], new SportRange(2, 2));

//        foreach( $firstRoundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
//            (new \SportsPlanning\Output\Game())->output($game);
//        }

        $competitionStartDateTime = $competition->getStartDateTime();

        $secondRoundNumberStartDateTimeTmp = $this->getStartSecond($competitionStartDateTime);

        $blockedPeriod = new Period(
            $secondRoundNumberStartDateTimeTmp->modify("-1 minutes"),
            $secondRoundNumberStartDateTimeTmp->modify("+40 minutes")
        );
        $planningScheduler = new PlanningScheduler([$blockedPeriod]);
        $planningScheduler->rescheduleGames($firstRoundNumber);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40);
        self::assertEquals(
            $secondRoundNumberStartDateTime,
            $secondRoundNumber->getGames(GameOrder::ByPoule)[0]->getStartDateTime()
        );
    }

    public function testTwoBlockedPeriodNoGameBetween(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3]);

        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        (new GamesCreator())->createStructureGames($structure, [], new SportRange(2, 2));

        (new \Sports\Output\Games())->outputRoundNumber($firstRoundNumber);

        $competitionStartDateTime = $competition->getStartDateTime();

        $blockedPeriod = new Period(
            $competitionStartDateTime->modify('+15 minutes'),
            $competitionStartDateTime->modify('+30 minutes')
        );
        $blockedPeriod2 = new Period(
            $blockedPeriod->getEndDate()->modify('+15 minutes'),
            $blockedPeriod->getEndDate()->modify('+30 minutes')
        );
        $planningScheduler = new PlanningScheduler([$blockedPeriod, $blockedPeriod2]);
        $planningScheduler->rescheduleGames($firstRoundNumber);

        self::assertEquals($firstRoundNumber->getFirstStartDateTime(), $blockedPeriod2->getEndDate());
    }

    public function testRoundNumberNoGames(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3]);

        $rootRound = $structure->getRootRound();
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();
        self::assertNotNull($secondRoundNumber);

        (new GamesCreator())->createStructureGames($structure);

        $secondRoundNumber->getPoules()[0]->getAgainstGames()->clear();
//        foreach( $firstRoundNumber->getGames( Game::ORDER_BY_BATCH ) as $game ) {
//            (new \SportsPlanning\Output\Game())->output($game);
//        }

        $planningScheduler = new PlanningScheduler([]);
        self::expectException(Exception::class);
        $planningScheduler->rescheduleGames($firstRoundNumber);
    }

    protected function getStartSecond(DateTimeImmutable $startFirst, int $delta = 0): DateTimeImmutable
    {
        $planningConfigService = new PlanningConfigService();
        $addMinutes = 3 * $planningConfigService->getDefaultMinutesPerGame();
        $addMinutes += 2 * $planningConfigService->getDefaultMinutesBetweenGames();
        $addMinutes += $planningConfigService->getDefaultMinutesAfter();
        return $startFirst->modify("+" . ($addMinutes + $delta) . " minutes");
    }
}
