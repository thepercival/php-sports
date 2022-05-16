<?php

declare(strict_types=1);

namespace Sports\Tests\Ranking\Calculator;

use PHPUnit\Framework\TestCase;
use Sports\Game\State as GameState;
use Sports\Poule;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Ranking\AgainstRuleSet;
use Sports\Ranking\Calculator\Cumulative;
use Sports\Ranking\Calculator\Round as RoundRankingCalculator;
use Sports\Ranking\PointsCalculation;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\SetScores;
use Sports\TestHelper\StructureEditorCreator;
use Sports\Output\Game\Against as AgainstGameOutput;

class RoundTest extends TestCase
{
    use CompetitionCreator;
    use SetScores;
    use StructureEditorCreator;

    public function testMultipleEqualRanked(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setAgainstScore($pouleOne, 1, 2, 0, 0);
        $this->setAgainstScore($pouleOne, 1, 3, 0, 0);
        $this->setAgainstScore($pouleOne, 2, 3, 0, 0);

        $roundRankingCalculator = new RoundRankingCalculator();
        $items = $roundRankingCalculator->getItemsForPoule($pouleOne);
        foreach ($items as $item) {
            self::assertSame($item->getRank(), 1);
        }

        // cached items
        $cachedItems = $roundRankingCalculator->getItemsForPoule($pouleOne);
        foreach ($cachedItems as $item) {
            self::assertSame($item->getRank(), 1);
        }
    }

    public function testMultipleRuleThirdPlaceAgainstSportDiff(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $this->setAgainstScore($pouleOne, 1, 2, 1, 0);
        $this->setAgainstScore($pouleOne, 1, 3, 1, 0);
        $this->setAgainstScore($pouleOne, 2, 3, 0, 1);

        $pouleTwo = $rootRound->getPoule(2);
        $this->setAgainstScore($pouleTwo, 1, 2, 1, 0);
        $this->setAgainstScore($pouleTwo, 1, 3, 1, 0);
        $this->setAgainstScore($pouleTwo, 2, 3, 0, 2);

        $roundRankingCalculator = new RoundRankingCalculator();
        $nrsTwo = $rootRound->getHorizontalPoule(QualifyTarget::Winners, 2);
        $rankingItems = $roundRankingCalculator->getItemsForHorizontalPoule($nrsTwo);

        $thirdPlacedItem = $roundRankingCalculator->getItemByRank($rankingItems, 1);
        self::assertNotNull($thirdPlacedItem);

        self::assertSame($thirdPlacedItem->getPlace(), $pouleTwo->getPlace(3));
    }

    public function testMultipleRuleThirdPlaceAgainstSportTotallyEqual(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $this->setAgainstScore($pouleOne, 1, 2, 1, 0);
        $this->setAgainstScore($pouleOne, 1, 3, 1, 0);
        $this->setAgainstScore($pouleOne, 2, 3, 0, 1);

        $pouleTwo = $rootRound->getPoule(2);
        $this->setAgainstScore($pouleTwo, 1, 2, 1, 0);
        $this->setAgainstScore($pouleTwo, 1, 3, 1, 0);
        $this->setAgainstScore($pouleTwo, 2, 3, 0, 1);

        $roundRankingCalculator = new RoundRankingCalculator();
        $nrsTwo = $rootRound->getHorizontalPoule(QualifyTarget::Winners, 2);
        $rankingItems = $roundRankingCalculator->getItemsForHorizontalPoule($nrsTwo);

        $thirdPlacedItem = $roundRankingCalculator->getItemByRank($rankingItems, 1);
        self::assertNotNull($thirdPlacedItem);

        self::assertSame($thirdPlacedItem->getPlace(), $pouleOne->getPlace(3));
    }

    public function testSingleRankedStateFinished(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

//        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count(); $nr++) {
//            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $nr);
//            $pouleOne->getPlace($nr)->setCompetitor($competitor);
//        }

        $this->setAgainstScore($pouleOne, 1, 2, 2, 1);
        $this->setAgainstScore($pouleOne, 1, 3, 3, 1);
        $this->setAgainstScore($pouleOne, 2, 3, 3, 2);

        $roundRankingCalculator = new RoundRankingCalculator();
        $items = $roundRankingCalculator->getItemsForPoule($pouleOne);
        $roundRankingItem1 = $roundRankingCalculator->getItemByRank($items, 1);
        self::assertNotNull($roundRankingItem1);
        $roundRankingItem2 = $roundRankingCalculator->getItemByRank($items, 2);
        self::assertNotNull($roundRankingItem2);
        $roundRankingItem3 = $roundRankingCalculator->getItemByRank($items, 3);
        self::assertNotNull($roundRankingItem3);
        self::assertSame($roundRankingItem1->getPlace(), $pouleOne->getPlace(1));
        self::assertSame($roundRankingItem2->getPlace(), $pouleOne->getPlace(2));
        self::assertSame($roundRankingItem3->getPlace(), $pouleOne->getPlace(3));
    }

    public function testSingleRankedStateInProgressAndFinished(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setAgainstScore($pouleOne, 1, 2, 2, 1, GameState::InProgress);
        $this->setAgainstScore($pouleOne, 1, 3, 3, 1, GameState::InProgress);
        $this->setAgainstScore($pouleOne, 2, 3, 3, 2, GameState::InProgress);

        $roundRankingCalculator = new RoundRankingCalculator([GameState::InProgress, GameState::Finished]);
        $items = $roundRankingCalculator->getItemsForPoule($pouleOne);

        $roundRankingItem1 = $roundRankingCalculator->getItemByRank($items, 1);
        self::assertNotNull($roundRankingItem1);
        $roundRankingItem2 = $roundRankingCalculator->getItemByRank($items, 2);
        self::assertNotNull($roundRankingItem2);
        $roundRankingItem3 = $roundRankingCalculator->getItemByRank($items, 3);
        self::assertNotNull($roundRankingItem3);
        self::assertSame($roundRankingItem1->getPlace(), $pouleOne->getPlace(1));
        self::assertSame($roundRankingItem2->getPlace(), $pouleOne->getPlace(2));
        self::assertSame($roundRankingItem3->getPlace(), $pouleOne->getPlace(3));

        $roundRankingCalculator2 = new RoundRankingCalculator();
        $items2 = $roundRankingCalculator2->getItemsForPoule($pouleOne);
        foreach ($items2 as $item) {
            self::assertSame($item->getRank(), 1);
        }
    }

    public function testHorizontalRankedECWC(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);

        $this->setAgainstScore($pouleOne, 1, 2, 2, 1);
        $this->setAgainstScore($pouleOne, 1, 3, 3, 1);
        $this->setAgainstScore($pouleOne, 2, 3, 3, 2);

        $this->setAgainstScore($pouleTwo, 1, 2, 4, 2);
        $this->setAgainstScore($pouleTwo, 1, 3, 6, 2);
        $this->setAgainstScore($pouleTwo, 2, 3, 6, 4);
        // Rank 2.1, 1.1, 2.2, 1.2, 2.3, 1.3

        $roundRankingCalculator = new RoundRankingCalculator();
        $firstHorizontalPoule = $rootRound->getHorizontalPoule(QualifyTarget::Winners, 1);
        $placeLocations = $roundRankingCalculator->getPlacesForHorizontalPoule($firstHorizontalPoule);

        self::assertSame(2, $placeLocations[0]->getPouleNr());
        self::assertSame(1, $placeLocations[1]->getPouleNr());

        $competition->setAgainstRuleSet(AgainstRuleSet::AmongFirst);
        $roundRankingCalculator2 = new RoundRankingCalculator();
        $placeLocations2 = $roundRankingCalculator2->getPlacesForHorizontalPoule($firstHorizontalPoule);

        self::assertSame(2, $placeLocations2[0]->getPouleNr());
        self::assertSame(1, $placeLocations2[1]->getPouleNr());
    }

//    public function testHorizontalRankedNoSingleRule(): void
//    {
//        $competition = $this->createCompetition();
//
//        $structureEditor = new StructureService([]);
//        $structure = $structureEditor->create($competition, new PouleStructure([3,3]));
//        $rootRound = $this->getFirstCategory($structure)->getRootRound();
//
//        $structureEditor->addQualifier($rootRound, QualifyTarget::Winners);
//
//        (new GamesCreator())->createStructureGames($structure);
//
//        $pouleOne = $rootRound->getPoule(1);
//        $pouleTwo = $rootRound->getPoule(2);
//
//        $this->setAgainstScore($pouleOne, 1, 2, 2, 1);
//        $this->setAgainstScore($pouleOne, 1, 3, 3, 1);
//        $this->setAgainstScore($pouleOne, 2, 3, 3, 2);
//
//        $this->setAgainstScore($pouleTwo, 1, 2, 4, 2);
//        $this->setAgainstScore($pouleTwo, 1, 3, 6, 2);
//        $this->setAgainstScore($pouleTwo, 2, 3, 6, 4);
//
//        $roundRankingCalculator = new RoundRankingCalculator();
//        $firstHorizontalPoule = $rootRound->getHorizontalPoule(QualifyTarget::Winners, 1);
//        self::assertInstanceOf(HorizontalPoule::class, $firstHorizontalPoule);
//        $placeLocations = $roundRankingCalculator->getPlaceLocationsForHorizontalPoule($firstHorizontalPoule);
//
//        self::assertCount(0, $placeLocations);
//    }

    public function testSingleRankedECWC(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setAgainstScore($pouleOne, 1, 2, 1, 0);
        $this->setAgainstScore($pouleOne, 1, 3, 1, 0);
        $this->setAgainstScore($pouleOne, 1, 4, 0, 1);
        $this->setAgainstScore($pouleOne, 2, 3, 2, 0);
        $this->setAgainstScore($pouleOne, 2, 4, 1, 0);
        $this->setAgainstScore($pouleOne, 3, 4, 1, 0);
        // p pnt
        // 1   6 (2-1)
        // 2   6 (3-1)
        // 3   3 (1-3)
        // 4   3 (1-2)

        $roundRankingCalculator = new RoundRankingCalculator();
        $items = $roundRankingCalculator->getItemsForPoule($pouleOne);
        $roundRankingItem1 = $roundRankingCalculator->getItemByRank($items, 1);
        self::assertNotNull($roundRankingItem1);
        $roundRankingItem2 = $roundRankingCalculator->getItemByRank($items, 2);
        self::assertNotNull($roundRankingItem2);
        self::assertSame($roundRankingItem1->getPlace(), $pouleOne->getPlace(2));
        self::assertSame($roundRankingItem2->getPlace(), $pouleOne->getPlace(1));

        $competition->setAgainstRuleSet(AgainstRuleSet::AmongFirst);
        $roundRankingCalculatorAmong = new RoundRankingCalculator();
        $itemsAmongFirst = $roundRankingCalculatorAmong->getItemsForPoule($pouleOne);

        $roundRankingItemAmongFirst1 = $roundRankingCalculatorAmong->getItemByRank($itemsAmongFirst, 1);
        self::assertNotNull($roundRankingItemAmongFirst1);
        $roundRankingItemAmongFirst2 = $roundRankingCalculatorAmong->getItemByRank($itemsAmongFirst, 2);
        self::assertNotNull($roundRankingItemAmongFirst2);
        self::assertSame($roundRankingItemAmongFirst1->getPlace(), $pouleOne->getPlace(1));
        self::assertSame($roundRankingItemAmongFirst2->getPlace(), $pouleOne->getPlace(2));
    }

    public function testVariation1MostPoints(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setAgainstScore($pouleOne, 1, 2, 1, 2);
        $this->setAgainstScore($pouleOne, 1, 3, 1, 3);
        $this->setAgainstScore($pouleOne, 2, 3, 2, 3);

        $roundRankingCalculator = new RoundRankingCalculator();
        $items = $roundRankingCalculator->getItemsForPoule($pouleOne);

        $roundRankingItem1 = $roundRankingCalculator->getItemByRank($items, 1);
        self::assertNotNull($roundRankingItem1);
        $roundRankingItem2 = $roundRankingCalculator->getItemByRank($items, 2);
        self::assertNotNull($roundRankingItem2);
        $roundRankingItem3 = $roundRankingCalculator->getItemByRank($items, 3);
        self::assertNotNull($roundRankingItem3);
        self::assertSame($roundRankingItem1->getPlace(), $pouleOne->getPlace(3));
        self::assertSame($roundRankingItem2->getPlace(), $pouleOne->getPlace(2));
        self::assertSame($roundRankingItem3->getPlace(), $pouleOne->getPlace(1));
    }

    public function testVariation2FewestGames(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setAgainstScore($pouleOne, 1, 2, 5, 0);
        $this->setAgainstScore($pouleOne, 1, 3, 0, 1);
        $this->setAgainstScore($pouleOne, 1, 4, 1, 1);
        $this->setAgainstScore($pouleOne, 2, 3, 0, 0);
        // $this->setAgainstScore(pouleOne, 2, 4, 0, 1);
        $this->setAgainstScore($pouleOne, 3, 4, 0, 1);

        $roundRankingCalculator = new RoundRankingCalculator();
        $items = $roundRankingCalculator->getItemsForPoule($pouleOne);

        $roundRankingItem1 = $roundRankingCalculator->getItemByRank($items, 1);
        self::assertNotNull($roundRankingItem1);
        $roundRankingItem2 = $roundRankingCalculator->getItemByRank($items, 2);
        self::assertNotNull($roundRankingItem2);
        $roundRankingItem3 = $roundRankingCalculator->getItemByRank($items, 3);
        self::assertNotNull($roundRankingItem3);
        self::assertSame($roundRankingItem1->getPlace(), $pouleOne->getPlace(4));
        self::assertSame($roundRankingItem2->getPlace(), $pouleOne->getPlace(1));
        self::assertSame($roundRankingItem3->getPlace(), $pouleOne->getPlace(3));
    }

    public function testVariation3FewestGames(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        // $this->setAgainstScore($pouleOne, 1, 2, 1, 0);
        $this->setAgainstScore($pouleOne, 1, 3, 1, 0);
        $this->setAgainstScore($pouleOne, 1, 4, 1, 1);
        $this->setAgainstScore($pouleOne, 2, 3, 0, 0);
        $this->setAgainstScore($pouleOne, 2, 4, 0, 5);
        $this->setAgainstScore($pouleOne, 3, 4, 3, 0);

        $roundRankingCalculator = new RoundRankingCalculator();
        $items = $roundRankingCalculator->getItemsForPoule($pouleOne);

        $roundRankingItem1 = $roundRankingCalculator->getItemByRank($items, 1);
        self::assertNotNull($roundRankingItem1);
        $roundRankingItem2 = $roundRankingCalculator->getItemByRank($items, 2);
        self::assertNotNull($roundRankingItem2);
        $roundRankingItem3 = $roundRankingCalculator->getItemByRank($items, 3);
        self::assertNotNull($roundRankingItem3);
        self::assertSame($roundRankingItem1->getPlace(), $pouleOne->getPlace(1));
        self::assertSame($roundRankingItem2->getPlace(), $pouleOne->getPlace(4));
        self::assertSame($roundRankingItem3->getPlace(), $pouleOne->getPlace(3));
    }

    public function testVariation4MostScored(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setAgainstScore($pouleOne, 1, 2, 1, 1);
        $this->setAgainstScore($pouleOne, 1, 3, 2, 1);
        $this->setAgainstScore($pouleOne, 2, 3, 1, 0);

        $roundRankingCalculator = new RoundRankingCalculator();
        $items = $roundRankingCalculator->getItemsForPoule($pouleOne);

        $roundRankingItem1 = $roundRankingCalculator->getItemByRank($items, 1);
        self::assertNotNull($roundRankingItem1);
        $roundRankingItem2 = $roundRankingCalculator->getItemByRank($items, 2);
        self::assertNotNull($roundRankingItem2);
        $roundRankingItem3 = $roundRankingCalculator->getItemByRank($items, 3);
        self::assertNotNull($roundRankingItem3);
        self::assertSame($roundRankingItem1->getPlace(), $pouleOne->getPlace(1));
        self::assertSame($roundRankingItem2->getPlace(), $pouleOne->getPlace(2));
        self::assertSame($roundRankingItem3->getPlace(), $pouleOne->getPlace(3));
    }

    public function testVariation5AgainstEachOtherNoGames(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        // 3 gelijk laten eindigen
        $this->setAgainstScore($pouleOne, 1, 2, 1, 0);
        // setAgainstScore(pouleOne, 1, 3, 1, 0);
        // setAgainstScore(pouleOne, 1, 4, 1, 1);
        $this->setAgainstScore($pouleOne, 2, 3, 0, 1);
        $this->setAgainstScore($pouleOne, 2, 4, 0, 1);
        // setAgainstScore(pouleOne, 3, 4, 3, 0);

        $roundRankingCalculator = new RoundRankingCalculator();
        $items = $roundRankingCalculator->getItemsForPoule($pouleOne);

        $roundRankingItem = $roundRankingCalculator->getItemByRank($items, 4);
        self::assertNotNull($roundRankingItem);
        self::assertSame($roundRankingItem->getPlace(), $pouleOne->getPlace(2));
    }

    public function testVariation5AgainstEachOtherEqual(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        // 3 gelijk laten eindigen
        $this->setAgainstScore($pouleOne, 1, 2, 1, 0);
        $this->setAgainstScore($pouleOne, 1, 3, 1, 0);
        $this->setAgainstScore($pouleOne, 1, 4, 0, 1);
        $this->setAgainstScore($pouleOne, 2, 3, 0, 1);
        $this->setAgainstScore($pouleOne, 2, 4, 0, 1);
        $this->setAgainstScore($pouleOne, 3, 4, 1, 0);

        $roundRankingCalculator = new RoundRankingCalculator();
        $roundRankingItems = $roundRankingCalculator->getItemsForPoule($pouleOne);
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame($roundRankingItem->getRank(), 1);
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame($roundRankingItem->getRank(), 1);
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame($roundRankingItem->getRank(), 1);

        $roundRankingItems = $roundRankingCalculator->getItemsForPoule($pouleOne);
        $roundRankingItem = $roundRankingCalculator->getItemByRank($roundRankingItems, 4);
        self::assertNotNull($roundRankingItem);
        self::assertSame($roundRankingItem->getPlace(), $pouleOne->getPlace(2));
    }

    public function testPointsCalculationIsScoresOnly(): void
    {
        $competition = $this->createCompetition();


        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();
        $qualifyConfig = $rootRound->getAgainstQualifyConfig($competition->getSingleSport());
        self::assertNotNull($qualifyConfig);
        $qualifyConfig->setPointsCalculation(PointsCalculation::Scores);

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        // 3 gelijk laten eindigen
        $this->setAgainstScore($pouleOne, 1, 2, 7, 6);
        $this->setAgainstScore($pouleOne, 1, 3, 7, 0);
        $this->setAgainstScore($pouleOne, 1, 4, 7, 0);
        $this->setAgainstScore($pouleOne, 2, 3, 0, 1);
        $this->setAgainstScore($pouleOne, 2, 4, 0, 1);
        $this->setAgainstScore($pouleOne, 3, 4, 1, 0);

        $roundRankingCalculator = new RoundRankingCalculator();
        $roundRankingItems = $roundRankingCalculator->getItemsForPoule($pouleOne);
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame($roundRankingItem->getRank(), 1);
        self::assertSame($roundRankingItem->getPlace(), $pouleOne->getPlace(1));
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame($roundRankingItem->getRank(), 2);
        self::assertSame($roundRankingItem->getPlace(), $pouleOne->getPlace(2));
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame($roundRankingItem->getRank(), 3);
        self::assertSame($roundRankingItem->getPlace(), $pouleOne->getPlace(3));
    }

    public function testPlaceExtraPoints(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getFirstPoule();
        $pouleOne->getPlace(1)->setExtraPoints(-4);

        $this->setAgainstScore($pouleOne, 1, 2, 1, 0);
        $this->setAgainstScore($pouleOne, 1, 3, 1, 0);
        $this->setAgainstScore($pouleOne, 2, 3, 1, 0);

        $roundRankingCalculator = new RoundRankingCalculator();
        $roundRankingItems = $roundRankingCalculator->getItemsForPoule($pouleOne);
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame($pouleOne->getPlace(2), $roundRankingItem->getPlace());
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame($pouleOne->getPlace(1), $roundRankingItem->getPlace(),);
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame($pouleOne->getPlace(3), $roundRankingItem->getPlace(),);
    }

    public function testAgainstsGameExtraPoints(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getFirstPoule();

        $this->setAgainstScore($pouleOne, 1, 2, 1, 0, GameState::Finished, -4);
        $this->setAgainstScore($pouleOne, 1, 3, 1, 0);
        $this->setAgainstScore($pouleOne, 2, 3, 1, 0, GameState::Finished, 0, 4);

        $roundRankingCalculator = new RoundRankingCalculator();
        $roundRankingItems = $roundRankingCalculator->getItemsForPoule($pouleOne);
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame($pouleOne->getPlace(3), $roundRankingItem->getPlace());
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame($pouleOne->getPlace(2), $roundRankingItem->getPlace(),);
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame($pouleOne->getPlace(1), $roundRankingItem->getPlace(),);
    }

    public function test2SportsByRank(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(1),
            $this->getAgainstGppSportVariantWithFields(1),
            $this->getAgainstGppSportVariantWithFields(1)
        ];
        $competition = $this->createCompetition($sportVariantsWithFields);

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4]);
        $poule = $this->getFirstCategory($structure)->getRootRound()->getFirstPoule();

        (new GamesCreator())->createStructureGames($structure);

        $this->setScoresHelper($poule);

//        $outputGame = new AgainstGameOutput();
//        $games = $poule->getAgainstGames();
//        foreach ($games as $gameIt) {
//            $outputGame->output($gameIt);
//        }

        $roundRankingCalculator = new RoundRankingCalculator();
        $roundRankingItems = $roundRankingCalculator->getItemsForPoule($poule);
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame(4, $roundRankingItem->getPlace()->getPlaceNr());
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame(1, $roundRankingItem->getPlace()->getPlaceNr());
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame(3, $roundRankingItem->getPlace()->getPlaceNr());
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame(2, $roundRankingItem->getPlace()->getPlaceNr());
    }

    public function test2SportsByPerformance(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(1),
            $this->getAgainstGppSportVariantWithFields(1),
            $this->getAgainstGppSportVariantWithFields(1)
        ];
        $competition = $this->createCompetition($sportVariantsWithFields);

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4]);
        $poule = $this->getFirstCategory($structure)->getRootRound()->getFirstPoule();

        (new GamesCreator())->createStructureGames($structure);

        $this->setScoresHelper($poule);

//        $outputGame = new AgainstGameOutput();
//        $games = $poule->getAgainstGames();
//        foreach ($games as $gameIt) {
//            $outputGame->output($gameIt);
//        }

        $roundRankingCalculator = new RoundRankingCalculator(null, Cumulative::ByPerformance);
        $roundRankingItems = $roundRankingCalculator->getItemsForPoule($poule);
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame(1, $roundRankingItem->getPlace()->getPlaceNr());
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame(4, $roundRankingItem->getPlace()->getPlaceNr());
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame(3, $roundRankingItem->getPlace()->getPlaceNr());
        $roundRankingItem = array_shift($roundRankingItems);
        self::assertNotNull($roundRankingItem);
        self::assertSame(2, $roundRankingItem->getPlace()->getPlaceNr());
    }

    protected function setScoresHelper(Poule $poule): void
    {
        $this->setAgainstScore($poule, 1, 2, 6, 0); // V
        $this->setAgainstScore($poule, 3, 4, 1, 0); // V
        $this->setAgainstScore($poule, 1, 4, 0, 3); // S2
        $this->setAgainstScore($poule, 2, 3, 0, 2); // S2
        $this->setAgainstScore($poule, 2, 4, 0, 1); // S3
        $this->setAgainstScore($poule, 1, 3, 1, 0); // S3
        // pl     rank      pnt    saldo        cumulativeRank
        //                                      V   S2      S3      TOT        RANK
        //  1        1        6      7-3        1    4      1        6          2
        //  2        4        0      0-9        4    3      2        9          4
        //  3        3        6      3-1        2    2      2        6          3
        //  4        2        6      4-1        3    1      1        5          1
    }
}
