<?php

declare(strict_types=1);

namespace Sports\Tests\Ranking\Calculator;

use PHPUnit\Framework\TestCase;
use Sports\Competitor\StartLocation;
use Sports\Place\Location as PlaceLocation;
use Sports\Qualify\Distribution;
use Sports\Qualify\Service as QualifyService;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Ranking\Calculator\End as EndRankingCalculator;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\SetScores;
use Sports\TestHelper\StructureEditorCreator;
use Sports\Output\StructureOutput;

class EndTest extends TestCase
{
    use CompetitionCreator;
    use SetScores;
    use StructureEditorCreator;

    public function testOnePouleOfThreePlaces(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3]);
        $firstCategory = $structure->getSingleCategory();
        $rootRound = $firstCategory->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setAgainstScore($pouleOne, 1, 2, 2, 1);
        $this->setAgainstScore($pouleOne, 1, 3, 3, 1);
        $this->setAgainstScore($pouleOne, 2, 3, 3, 2);

        $calculator = new EndRankingCalculator($firstCategory);
        $items = $calculator->getItems();

        for ($rank = 1; $rank <= count($items); $rank++) {
            $endRankingItem = array_shift($items);
            // self::assertInstanceOf(EndRankingItem::class, $endRankingItem);
            $startLocation = $endRankingItem->getStartLocation();
            self::assertInstanceOf(StartLocation::class, $startLocation);
            self::assertSame($startLocation->getPlaceNr(), $rank);
            self::assertSame($endRankingItem->getUniqueRank(), $rank);
        }
    }

    public function testOnePouleOfThreePlacesNotPlayed(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3]);
        $firstCategory = $structure->getSingleCategory();
        $rootRound = $firstCategory->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setAgainstScore($pouleOne, 1, 2, 2, 1);
        $this->setAgainstScore($pouleOne, 1, 3, 3, 1);
        // $this->setAgainstScore($pouleOne, 2, 3, 3, 2);

        $calculator = new EndRankingCalculator($firstCategory);
        $items = $calculator->getItems();

        for ($rank = 1; $rank <= count($items); $rank++) {
            $endRankingItem = array_shift($items);
            // self::assertInstanceOf(EndRankingItem::class, $endRankingItem);
            self::assertNull($endRankingItem->getStartLocation());
        }
    }

    public function testTwoRoundNumbers5(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5]);
        $firstCategory = $structure->getSingleCategory();
        $rootRound = $firstCategory->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setAgainstScore($pouleOne, 1, 2, 2, 1);
        $this->setAgainstScore($pouleOne, 1, 3, 3, 1);
        $this->setAgainstScore($pouleOne, 1, 4, 4, 1);
        $this->setAgainstScore($pouleOne, 1, 5, 5, 1);
        $this->setAgainstScore($pouleOne, 2, 3, 3, 2);
        $this->setAgainstScore($pouleOne, 2, 4, 4, 2);
        $this->setAgainstScore($pouleOne, 2, 5, 5, 2);
        $this->setAgainstScore($pouleOne, 3, 4, 4, 3);
        $this->setAgainstScore($pouleOne, 3, 5, 5, 3);
        $this->setAgainstScore($pouleOne, 4, 5, 5, 4);

        $winnersPoule = $winnersRound->getPoule(1);
        $this->setAgainstScore($winnersPoule, 1, 2, 2, 1);
        $loserssPoule = $losersRound->getPoule(1);
        $this->setAgainstScore($loserssPoule, 1, 2, 2, 1);

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers();

        $calculator = new EndRankingCalculator($firstCategory);
        $items = $calculator->getItems();

        for ($rank = 1; $rank <= count($items); $rank++) {
            $endRankingItem = array_shift($items);
            // self::assertNotNull($endRankingItem);
            $startLocation = $endRankingItem->getStartLocation();
            self::assertInstanceOf(StartLocation::class, $startLocation);
            self::assertSame($startLocation->getPlaceNr(), $rank);
        }
    }

    // 2 roundnumbers, [4,4,4] => (W[5],(L[5])
    public function testTwoRoundNumbers444ToW5L5(): void
    {

        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4]);

        // $competitorMap = new StartLocationMap(createTeamCompetitors($competition, $structure.getRootRounds()));

        $defaultCategory = $structure->getSingleCategory();
        $rootRound = $defaultCategory->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [5], Distribution::Vertical);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [5], Distribution::Vertical);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);
        $pouleThree = $rootRound->getPoule(3);

        (new GamesCreator())->createStructureGames($structure);

        $this->setAgainstScore($pouleOne, 1, 2, 2, 1); // 1 9p
        $this->setAgainstScore($pouleOne, 1, 3, 3, 1); // 2 6p
        $this->setAgainstScore($pouleOne, 1, 4, 4, 1); // 3 3p
        $this->setAgainstScore($pouleOne, 2, 3, 3, 2); // 4 0p
        $this->setAgainstScore($pouleOne, 2, 4, 4, 2);
        $this->setAgainstScore($pouleOne, 3, 4, 4, 3);

        $this->setAgainstScore($pouleTwo, 1, 2, 4, 2); // 1 9p
        $this->setAgainstScore($pouleTwo, 1, 3, 6, 2); // 2 6p
        $this->setAgainstScore($pouleTwo, 1, 4, 8, 2); // 3 3p
        $this->setAgainstScore($pouleTwo, 2, 3, 6, 4); // 4 0p
        $this->setAgainstScore($pouleTwo, 2, 4, 8, 4);
        $this->setAgainstScore($pouleTwo, 3, 4, 8, 6);

        $this->setAgainstScore($pouleThree, 1, 2, 8, 4); // 1 9p
        $this->setAgainstScore($pouleThree, 1, 3, 12, 4); // 2 6p
        $this->setAgainstScore($pouleThree, 1, 4, 16, 4); // 3 3p
        $this->setAgainstScore($pouleThree, 2, 3, 12, 8); // 4 0p
        $this->setAgainstScore($pouleThree, 2, 4, 16, 8);
        $this->setAgainstScore($pouleThree, 3, 4, 16, 12);

        $winnersPoule1 = $winnersRound->getPoule(1);

        $this->setAgainstScore($winnersPoule1, 1, 2, 2, 1); // 1 12p
        $this->setAgainstScore($winnersPoule1, 1, 3, 3, 1); // 2 9p
        $this->setAgainstScore($winnersPoule1, 1, 4, 4, 1); // 3 6p
        $this->setAgainstScore($winnersPoule1, 1, 5, 5, 1); // 4 3p
        $this->setAgainstScore($winnersPoule1, 2, 3, 3, 2); // 5 0p
        $this->setAgainstScore($winnersPoule1, 2, 4, 4, 2);
        $this->setAgainstScore($winnersPoule1, 2, 5, 5, 2);
        $this->setAgainstScore($winnersPoule1, 3, 4, 4, 3);
        $this->setAgainstScore($winnersPoule1, 3, 5, 5, 3);
        $this->setAgainstScore($winnersPoule1, 4, 5, 5, 4);

        $losersPoule1 = $losersRound->getPoule(1);

        $this->setAgainstScore($losersPoule1, 1, 2, 2, 1); // 1 12p
        $this->setAgainstScore($losersPoule1, 1, 3, 3, 1); // 2 9p
        $this->setAgainstScore($losersPoule1, 1, 4, 4, 1); // 3 6p
        $this->setAgainstScore($losersPoule1, 1, 5, 5, 1); // 4 3p
        $this->setAgainstScore($losersPoule1, 2, 3, 3, 2); // 5 0p
        $this->setAgainstScore($losersPoule1, 2, 4, 4, 2);
        $this->setAgainstScore($losersPoule1, 2, 5, 5, 2);
        $this->setAgainstScore($losersPoule1, 3, 4, 4, 3);
        $this->setAgainstScore($losersPoule1, 3, 5, 5, 3);
        $this->setAgainstScore($losersPoule1, 4, 5, 5, 4);

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers();

//        (new StructureOutput())->output($structure);

        $calculator = new EndRankingCalculator($defaultCategory);
        $items = $calculator->getItems();

        $rank1 = array_shift($items);
        self::assertNotNull($rank1);
        self::assertSame(1, $rank1->getUniqueRank());
        $startLocation = $rank1->getStartLocation();
        self::assertInstanceOf(StartLocation::class, $startLocation);
        self::assertSame(3, $startLocation->getPouleNr());
        self::assertSame(1, $startLocation->getPlaceNr());

        $rank2 = array_shift($items);
        self::assertNotNull($rank2);
        self::assertSame(2, $rank2->getUniqueRank());
        $startLocation = $rank2->getStartLocation();
        self::assertInstanceOf(StartLocation::class, $startLocation);
        self::assertSame(2, $startLocation->getPouleNr());
        self::assertSame(1, $startLocation->getPlaceNr());

        $rank3 = array_shift($items);
        self::assertNotNull($rank3);
        self::assertSame(3, $rank3->getUniqueRank());
        $startLocation = $rank3->getStartLocation();
        self::assertInstanceOf(StartLocation::class, $startLocation);
        self::assertSame(1, $startLocation->getPouleNr());
        self::assertSame(1, $startLocation->getPlaceNr());

        $rank4 = array_shift($items);
        self::assertNotNull($rank4);
        self::assertSame(4, $rank4->getUniqueRank());
        $startLocation = $rank4->getStartLocation();
        self::assertInstanceOf(StartLocation::class, $startLocation);
        self::assertSame(3, $startLocation->getPouleNr());
        self::assertSame(2, $startLocation->getPlaceNr());

        $rank5 = array_shift($items);
        self::assertNotNull($rank5);
        self::assertSame(5, $rank5->getUniqueRank());
        $startLocation = $rank5->getStartLocation();
        self::assertInstanceOf(StartLocation::class, $startLocation);
        self::assertSame(2, $startLocation->getPouleNr());
        self::assertSame(2, $startLocation->getPlaceNr());

        $rank6 = array_shift($items);
        self::assertNotNull($rank6);
        self::assertSame(6, $rank6->getUniqueRank());
        $startLocation = $rank6->getStartLocation();
        self::assertInstanceOf(StartLocation::class, $startLocation);
        self::assertSame(1, $startLocation->getPouleNr());
        self::assertSame(2, $startLocation->getPlaceNr());

        $rank7 = array_shift($items);
        self::assertNotNull($rank7);
        self::assertSame(7, $rank7->getUniqueRank());
        $startLocation = $rank7->getStartLocation();
        self::assertInstanceOf(StartLocation::class, $startLocation);
        self::assertSame(1, $startLocation->getPouleNr());
        self::assertSame(3, $startLocation->getPlaceNr());

        $rank8 = array_shift($items);
        self::assertNotNull($rank8);
        self::assertSame(8, $rank8->getUniqueRank());
        $startLocation = $rank8->getStartLocation();
        self::assertInstanceOf(StartLocation::class, $startLocation);
        self::assertSame(2, $startLocation->getPouleNr());
        self::assertSame(3, $startLocation->getPlaceNr());

        $rank9 = array_shift($items);
        self::assertNotNull($rank9);
        self::assertSame(9, $rank9->getUniqueRank());
        $startLocation = $rank9->getStartLocation();
        self::assertInstanceOf(StartLocation::class, $startLocation);
        self::assertSame(3, $startLocation->getPouleNr());
        self::assertSame(3, $startLocation->getPlaceNr());

        $rank10 = array_shift($items);
        self::assertNotNull($rank10);
        self::assertSame(10, $rank10->getUniqueRank());
        $startLocation = $rank10->getStartLocation();
        self::assertInstanceOf(StartLocation::class, $startLocation);
        self::assertSame(1, $startLocation->getPouleNr());
        self::assertSame(4, $startLocation->getPlaceNr());

        $rank11 = array_shift($items);
        self::assertNotNull($rank11);
        $startLocation = $rank11->getStartLocation();
        self::assertSame(11, $rank11->getUniqueRank());
        self::assertInstanceOf(StartLocation::class, $startLocation);
        self::assertSame(2, $startLocation->getPouleNr());
        self::assertSame(4, $startLocation->getPlaceNr());

        $rank12 = array_shift($items);
        self::assertNotNull($rank12);
        self::assertSame(12, $rank12->getUniqueRank());
        $startLocation = $rank12->getStartLocation();
        self::assertInstanceOf(StartLocation::class, $startLocation);
        self::assertSame(3, $startLocation->getPouleNr());
        self::assertSame(4, $startLocation->getPlaceNr());
    }
}
