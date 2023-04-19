<?php

declare(strict_types=1);

namespace Sports\Tests\Round\Number;

use PHPUnit\Framework\TestCase;
use Sports\Game\Order;
use Sports\Output\StructureOutput;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Round\Number\GamesValidator;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\StructureEditorCreator;
use SportsHelpers\SelfReferee;
use Sports\Output\Game\Against as AgainstGameOutput;

final class PlanningAssignerTest extends TestCase
{
    use CompetitionCreator;
    use StructureEditorCreator;

    public function testValid(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [2,2]);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        (new GamesCreator())->createGames($firstRoundNumber);

        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        self::expectNotToPerformAssertions();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testWithRefereePlaces(): void
    {
        $competition = $this->createCompetition();
        $competition->getReferees()->clear();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4]);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        (new GamesCreator())->createGames($firstRoundNumber);

        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        self::expectNotToPerformAssertions();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testDifferentPouleSizes(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [6,5]);

        $rootRound = $structure->getSingleCategory()->getRootRound();
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [7]);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $firstRoundNumber->getValidPlanningConfig()->setSelfReferee(SelfReferee::SamePoule);
        $secondRoundNumber = $firstRoundNumber->getNext();
        self::assertNotNull($secondRoundNumber);

        (new GamesCreator())->createStructureGames($structure);

        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        // self::expectNotToPerformAssertions();
        $gamesValidator->validate($secondRoundNumber, $nrOfReferees);
    }

    public function testWinPlacesWhichCanQualifyForNextRound(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4, 4, 4, 4]);

        $rootRound = $structure->getSingleCategory()->getRootRound();
        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2, 2]);
        $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2, 2, 2]);
        $structureEditor->addChildRound($winnersRound, QualifyTarget::Winners, [3]);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();
        self::assertNotNull($secondRoundNumber);

        (new GamesCreator())->createStructureGames($structure);

        foreach ($secondRoundNumber->getGames(Order::ByBatch) as $game) {
            $parentQualifyGroup = $game->getPoule()->getRound()->getParentQualifyGroup();
            self::assertNotNull($parentQualifyGroup);
            $target = $parentQualifyGroup->getTarget();
            if ($game->getBatchNr() <= 3) {
                self::assertEquals(QualifyTarget::Winners, $target);
            } else {
                self::assertEquals(QualifyTarget::Losers, $target);
            }
            // (new AgainstGameOutput())->output($game);
        }
    }

    public function testPlacesWinFirst(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4, 4, 4, 4]);

        $rootRound = $structure->getSingleCategory()->getRootRound();
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2, 2]);
        $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2, 2, 2]);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();
        self::assertNotNull($secondRoundNumber);

        (new GamesCreator())->createStructureGames($structure);

        foreach ($secondRoundNumber->getGames(Order::ByBatch) as $game) {
            $parentQualifyGroup = $game->getPoule()->getRound()->getParentQualifyGroup();
            self::assertNotNull($parentQualifyGroup);
            $target = $parentQualifyGroup->getTarget();
            if ($game->getBatchNr() <= 3) {
                self::assertEquals(QualifyTarget::Losers, $target);
            } else {
                self::assertEquals(QualifyTarget::Winners, $target);
            }
            //(new AgainstGameOutput())->output($game);
        }
    }

    public function testWinPlacesWhichCanQualifyForNextRoundHard(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4, 4]);

        $rootRound = $structure->getSingleCategory()->getRootRound();
        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2, 2, 2]);
        $thirdsRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2, 2]);
        $fourthsRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2, 2]);

        // second round
        $winnersWinnersRound = $structureEditor->addChildRound($winnersRound, QualifyTarget::Winners, [2, 2]);
        $winnersLosersRound = $structureEditor->addChildRound($winnersRound, QualifyTarget::Losers, [2, 2]);
        $structureEditor->addChildRound($thirdsRound, QualifyTarget::Winners, [2]);
        $structureEditor->addChildRound($thirdsRound, QualifyTarget::Losers, [2]);
        $structureEditor->addChildRound($fourthsRound, QualifyTarget::Winners, [2]);
        $structureEditor->addChildRound($fourthsRound, QualifyTarget::Losers, [2]);

        // third round
        $structureEditor->addChildRound($winnersWinnersRound, QualifyTarget::Winners, [2]);
        $structureEditor->addChildRound($winnersWinnersRound, QualifyTarget::Losers, [2]);
        $structureEditor->addChildRound($winnersLosersRound, QualifyTarget::Winners, [2]);
        $structureEditor->addChildRound($winnersLosersRound, QualifyTarget::Losers, [2]);

//        (new StructureOutput())->output($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();
        self::assertNotNull($secondRoundNumber);

        (new GamesCreator())->createStructureGames($structure);

        foreach ($secondRoundNumber->getGames(Order::ByBatch) as $game) {
            $parentQualifyGroup = $game->getPoule()->getRound()->getParentQualifyGroup();
            self::assertNotNull($parentQualifyGroup);
            $target = $parentQualifyGroup->getTarget();
            if ($game->getBatchNr() < 5 ) {
                self::assertEquals(QualifyTarget::Winners, $target);
            } else {
                self::assertGreaterThan(1, $parentQualifyGroup->getNumber());
            }
//            (new AgainstGameOutput())->output($game);
        }
    }
}
