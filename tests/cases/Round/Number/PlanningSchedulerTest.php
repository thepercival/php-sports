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
        $structure = $structureEditor->create($competition, [3, 3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();
        self::assertNotNull($secondRoundNumber);

        (new GamesCreator())->createStructureGames($structure, [], new SportRange(2, 2));

        $competitionStartDateTime = $competition->getStartDateTime();

        $planningScheduler = new PlanningScheduler([]);
        $planningScheduler->rescheduleGames($firstRoundNumber, $competitionStartDateTime);

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

        $rootRound = $structure->getSingleCategory()->getRootRound();
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

        $competitionStart = $competitionStartDateTime->sub(new \DateInterval('PT1M'));
        self::assertInstanceOf(\DateTimeImmutable::class, $competitionStart);

        $blockedPeriod = new Period(
            $competitionStart,
            $competitionStartDateTime->add(new \DateInterval('PT' . (40 - 1) . 'M'))
        );
        $planningScheduler = new PlanningScheduler([$blockedPeriod]);
        $planningScheduler->rescheduleGames($firstRoundNumber, $competitionStartDateTime);

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

        $rootRound = $structure->getSingleCategory()->getRootRound();
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

        $secondBatchGameStart = $secondBatchGame->getStartDateTime()->sub(new \DateInterval('PT1M'));
        self::assertInstanceOf(\DateTimeImmutable::class, $secondBatchGameStart);

        $blockedPeriod = new Period(
            $secondBatchGameStart,
            $secondBatchGame->getStartDateTime()->add(new \DateInterval('PT40M'))
        );
        $planningScheduler = new PlanningScheduler([$blockedPeriod]);
        $planningScheduler->rescheduleGames($firstRoundNumber, $competitionStartDateTime);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40);
        self::assertEquals(
            $secondRoundNumberStartDateTime,
            $secondRoundNumber->getGames(GameOrder::ByPoule)[0]->getStartDateTime()
        );
    }

    public function testBlockedPeriodDuringSecondBatchGame(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3]);

        $rootRound = $structure->getSingleCategory()->getRootRound();
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
            $secondBatchGame->getStartDateTime()->add(new \DateInterval('PT1M')),
            $secondBatchGame->getStartDateTime()->add(new \DateInterval('PT40M'))
        );
        $planningScheduler = new PlanningScheduler([$blockedPeriod]);
        $planningScheduler->rescheduleGames($firstRoundNumber, $competitionStartDateTime);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames(GameOrder::ByPoule)[0]->getStartDateTime());
    }

    public function testBlockedPeriodAtStartSecondBatchGame(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3]);

        $rootRound = $structure->getSingleCategory()->getRootRound();
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
            $secondBatchGame->getStartDateTime()->add(new \DateInterval('PT40M'))
        );
        $planningScheduler = new PlanningScheduler([$blockedPeriod]);
        $planningScheduler->rescheduleGames($firstRoundNumber, $competitionStartDateTime);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames(GameOrder::ByPoule)[0]->getStartDateTime());
    }

    public function testBlockedPeriodBetweenRounds(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3]);

        $rootRound = $structure->getSingleCategory()->getRootRound();
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

        $secondRoundNumberStartTmp = $secondRoundNumberStartDateTimeTmp->sub(new \DateInterval('PT1M'));
        self::assertInstanceOf(\DateTimeImmutable::class, $secondRoundNumberStartTmp);

        $blockedPeriod = new Period(
            $secondRoundNumberStartTmp,
            $secondRoundNumberStartDateTimeTmp->add(new \DateInterval('PT40M'))
        );
        $planningScheduler = new PlanningScheduler([$blockedPeriod]);
        $planningScheduler->rescheduleGames($firstRoundNumber, $competitionStartDateTime);

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

        // $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        (new GamesCreator())->createStructureGames($structure, [], new SportRange(2, 2));

        // (new \Sports\Output\Games())->outputRoundNumber($firstRoundNumber);

        $competitionStartDateTime = $competition->getStartDateTime();

        $blockedPeriod = new Period(
            $competitionStartDateTime->add(new \DateInterval('PT15M')),
            $competitionStartDateTime->add(new \DateInterval('PT30M'))
        );
        $blockedPeriod2 = new Period(
            $blockedPeriod->getEndDate()->add(new \DateInterval('PT15M')),
            $blockedPeriod->getEndDate()->add(new \DateInterval('PT30M'))
        );
        $planningScheduler = new PlanningScheduler([$blockedPeriod, $blockedPeriod2]);
        $planningScheduler->rescheduleGames($firstRoundNumber, $competitionStartDateTime);

        self::assertEquals($firstRoundNumber->getFirstGameStartDateTime(), $blockedPeriod2->getEndDate());
    }

    public function testRoundNumberNoGames(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3]);

        $competitionStartDateTime = $competition->getStartDateTime();

        $rootRound = $structure->getSingleCategory()->getRootRound();
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
        $planningScheduler->rescheduleGames($firstRoundNumber, $competitionStartDateTime);
    }

    protected function getStartSecond(DateTimeImmutable $startFirst, int $delta = 0): DateTimeImmutable
    {
        $planningConfigService = new PlanningConfigService();
        $addMinutes = 3 * $planningConfigService->getDefaultMinutesPerGame();
        $addMinutes += 2 * $planningConfigService->getDefaultMinutesBetweenGames();
        $addMinutes += $planningConfigService->getDefaultMinutesAfter();
        return $startFirst->add(new \DateInterval('PT' . ($addMinutes + $delta) . 'M'));
    }
}
