<?php
declare(strict_types=1);

namespace Sports\Tests\Round\Number;

use PHPUnit\Framework\TestCase;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Round\Number\PlanningAssigner;
use Sports\Round\Number\PlanningMapper;
use Sports\Round\Number\PlanningScheduler;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\StructureEditorCreator;
use SportsHelpers\SportRange;
use SportsPlanning\Game as PlanningGame;

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
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $rootRound->getNumber();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);
        $secondRoundNumber = $losersRound->getNumber();
//        (new StructureOutput())->output($structure);

        $gamesCreator = new GamesCreator();
        $planningAssigner = new PlanningAssigner(new PlanningScheduler());
        $firstPlanning = $gamesCreator->createPlanning($firstRoundNumber, new SportRange(2, 2));
        $planningAssigner->assignPlanningToRoundNumber($firstRoundNumber, $firstPlanning);
        $secondPlanning = $gamesCreator->createPlanning($secondRoundNumber, new SportRange(2, 2));
        $planningAssigner->assignPlanningToRoundNumber($secondRoundNumber, $secondPlanning);

//        (new PlanningOutput())->outputWithGames($secondPlanning, true);
//        (new GamesOutput())->outputRoundNumber($secondRoundNumber);

        $planningMapper = new PlanningMapper($secondRoundNumber, $secondPlanning);
        $planningGames = $secondPlanning->getGames(PlanningGame::ORDER_BY_BATCH);
        $firstPlanningGame = reset($planningGames);
        $lastPlanningGame = end($planningGames);
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
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $rootRound->getNumber();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2, 2]);

        $secondRoundNumber = $winnersRound->getNumber();
//        (new StructureOutput())->output($structure);

        $gamesCreator = new GamesCreator();
        $planningAssigner = new PlanningAssigner(new PlanningScheduler());
        $firstPlanning = $gamesCreator->createPlanning($firstRoundNumber, new SportRange(2, 2));
        $planningAssigner->assignPlanningToRoundNumber($firstRoundNumber, $firstPlanning);
        $secondPlanning = $gamesCreator->createPlanning($secondRoundNumber, new SportRange(2, 2));
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

//    public function test4444to22222222(): void
//    {
//        $competition = $this->createCompetition();
//
//        $structureEditor = $this->createStructureEditor();
//        $structure = $structureEditor->create($competition, [4,4,4,4]);
//        $rootRound = $structure->getRootRound();
//        $firstRoundNumber = $rootRound->getNumber();
//
//        $firstPlacesRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2,2]);
//        $secondPlacesRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2,2]);
//        $thirdPlacesRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2,2]);
//        $fourthPlacesRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2,2]);
//
//        $secondRoundNumber = $firstPlacesRound->getNumber();
//        (new StructureOutput())->output($structure);
//
//        $gamesCreator = new GamesCreator();
//        $planningAssigner = new PlanningAssigner(new PlanningScheduler());
//        $firstPlanning = $gamesCreator->createPlanning($firstRoundNumber, new SportRange(2, 2));
//        $planningAssigner->assignPlanningToRoundNumber($firstRoundNumber, $firstPlanning);
//        $secondPlanning = $gamesCreator->createPlanning($secondRoundNumber, new SportRange(2, 2));
//        $planningAssigner->assignPlanningToRoundNumber($secondRoundNumber, $secondPlanning);
//
//        (new PlanningOutput())->outputWithGames($firstPlanning, true, );
//
////        (new GamesOutput())->outputRoundNumber($secondRoundNumber);
//
////        $planningMapper = new PlanningMapper($secondRoundNumber, $input);
////        $winnersPoule = $planningMapper->getPoule($input->getPoule(1));
////        $losersPoule = $planningMapper->getPoule($input->getPoule(2));
////
////        self::assertSame($winnersRound, $winnersPoule->getRound());
////        self::assertSame($losersRound, $losersPoule->getRound());
//    }
}
