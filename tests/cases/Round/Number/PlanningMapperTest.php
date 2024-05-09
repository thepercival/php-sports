<?php

declare(strict_types=1);

namespace Sports\Tests\Round\Number;

use PHPUnit\Framework\TestCase;
use Sports\Output\GamesOutput;
use Sports\Output\StructureOutput;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Round\Number\PlanningAssigner;
use Sports\Round\Number\PlanningMapper;
use Sports\Round\Number\PlanningScheduler;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\StructureEditorCreator;
use SportsHelpers\SportRange;
use SportsPlanning\Game as PlanningGame;
use SportsPlanning\Output\PlanningOutput;
use SportsPlanning\Output\PlanningOutput\Extra as PlanningOutputExtra;

final class PlanningMapperTest extends TestCase
{
    use CompetitionCreator;
    use StructureEditorCreator;

    public function testValid(): void
    {
        $competition = $this->createCompetition();
        // $competition->getSingleSport()->getFields()->removeElement($competition->getSingleSport()->getField(2));

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();
        $firstRoundNumber = $rootRound->getNumber();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);
        $secondRoundNumber = $losersRound->getNumber();
//        (new StructureOutput())->output($structure);

        $gamesCreator = new GamesCreator();
        $planningAssigner = new PlanningAssigner(new PlanningScheduler([]));
        $firstPlanning = $gamesCreator->createPlanning($firstRoundNumber, null, new SportRange(2, 2));
        $planningAssigner->assignPlanningToRoundNumber($firstRoundNumber, $firstPlanning);
        $secondPlanning = $gamesCreator->createPlanning($secondRoundNumber, null, new SportRange(2, 2));
        $planningAssigner->assignPlanningToRoundNumber($secondRoundNumber, $secondPlanning);

//        (new PlanningOutput())->outputWithGames($secondPlanning, true);
//        (new GamesOutput())->outputRoundNumber($secondRoundNumber);

        $planningMapper = new PlanningMapper($secondRoundNumber, $secondPlanning);
        $planningGames = $secondPlanning->getGames(PlanningGame::ORDER_BY_BATCH);
        $firstPlanningGame = reset($planningGames);
        $lastPlanningGame = end($planningGames);
        self::assertNotFalse($firstPlanningGame);
        self::assertNotFalse($lastPlanningGame);
        $losersPoule = $planningMapper->getPoule($firstPlanningGame->getPoule());
        $winnersPoule = $planningMapper->getPoule($lastPlanningGame->getPoule());

        self::assertSame($winnersRound, $winnersPoule->getRound());
        self::assertSame($losersRound, $losersPoule->getRound());
    }

    public function test44to2222(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();
        $firstRoundNumber = $rootRound->getNumber();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2, 2]);

        $secondRoundNumber = $winnersRound->getNumber();
//        (new StructureOutput())->output($structure);

        $gamesCreator = new GamesCreator();
        $planningAssigner = new PlanningAssigner(new PlanningScheduler([]));
        $firstPlanning = $gamesCreator->createPlanning($firstRoundNumber, null, new SportRange(2, 2));
        $planningAssigner->assignPlanningToRoundNumber($firstRoundNumber, $firstPlanning);
        $secondPlanning = $gamesCreator->createPlanning($secondRoundNumber, null, new SportRange(2, 2));
        $planningAssigner->assignPlanningToRoundNumber($secondRoundNumber, $secondPlanning);

//        (new PlanningOutput())->outputWithGames($secondPlanning, true, );
//
//        (new GamesOutput())->outputRoundNumber($secondRoundNumber);

        foreach ($winnersRound->getGames() as $winnersGame) {
            self::assertSame(2, $winnersGame->getBatchNr());
        }
        foreach ($losersRound->getGames() as $losersGame) {
            self::assertSame(1, $losersGame->getBatchNr());
        }
    }

    public function testBestLastOn(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();

        $structure = $structureEditor->create($competition, [4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();
        $firstRoundNumber = $rootRound->getNumber();
        $firstRoundNumber->getValidPlanningConfig()->setBestLast(true);

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2, 2]);

        $structureEditor->addChildRound($winnersRound, QualifyTarget::Winners, [2]);

//        (new StructureOutput())->output($structure);

        $secondRoundNumber = $winnersRound->getNumber();

        $gamesCreator = new GamesCreator();
        $planningAssigner = new PlanningAssigner(new PlanningScheduler([]));
        $firstPlanning = $gamesCreator->createPlanning($firstRoundNumber, null, new SportRange(2, 2));
        $planningAssigner->assignPlanningToRoundNumber($firstRoundNumber, $firstPlanning);
        $secondPlanning = $gamesCreator->createPlanning($secondRoundNumber, null, new SportRange(2, 2));
        $planningAssigner->assignPlanningToRoundNumber($secondRoundNumber, $secondPlanning);

//        (new PlanningOutput())->output($secondPlanning, PlanningOutputExtra::Games->value );
//         (new GamesOutput())->outputRoundNumber($secondRoundNumber);

        foreach ($winnersRound->getGames() as $winnersGame) {
            self::assertSame(2, $winnersGame->getBatchNr());
        }
        foreach ($losersRound->getGames() as $losersGame) {
            self::assertSame(1, $losersGame->getBatchNr());
        }
    }

    public function testBestLastOff(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();

        $structure = $structureEditor->create($competition, [4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();
        $firstRoundNumber = $rootRound->getNumber();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2, 2]);

        $structureEditor->addChildRound($winnersRound, QualifyTarget::Winners, [2]);

//        (new StructureOutput())->output($structure);

        $secondRoundNumber = $winnersRound->getNumber();

        $gamesCreator = new GamesCreator();
        $planningAssigner = new PlanningAssigner(new PlanningScheduler([]));
        $firstPlanning = $gamesCreator->createPlanning($firstRoundNumber, null, new SportRange(2, 2));
        $planningAssigner->assignPlanningToRoundNumber($firstRoundNumber, $firstPlanning);
        $secondPlanning = $gamesCreator->createPlanning($secondRoundNumber, null, new SportRange(2, 2));
        $planningAssigner->assignPlanningToRoundNumber($secondRoundNumber, $secondPlanning);

//        (new PlanningOutput())->output($secondPlanning, PlanningOutputExtra::Games->value );
        (new GamesOutput())->outputRoundNumber($secondRoundNumber);

        foreach ($winnersRound->getGames() as $winnersGame) {
            self::assertSame(1, $winnersGame->getBatchNr());
        }
        foreach ($losersRound->getGames() as $losersGame) {
            self::assertSame(2, $losersGame->getBatchNr());
        }
    }
}
