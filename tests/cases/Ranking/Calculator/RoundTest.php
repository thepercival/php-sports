<?php
declare(strict_types=1);

namespace Sports\Tests\Ranking\Calculator;

use Exception;
use PHPUnit\Framework\TestCase;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\SetScores;
use Sports\Structure\Service as StructureService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Ranking\Calculator\Round as RoundRankingCalculator;
use Sports\State;
use Sports\Ranking\RuleSet as RankingRuleSet;

class RoundTest extends TestCase
{
    use CompetitionCreator, SetScores;

    public function testMultipleEqualRanked(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setScoreSingle($pouleOne, 1, 2, 0, 0);
        $this->setScoreSingle($pouleOne, 1, 3, 0, 0);
        $this->setScoreSingle($pouleOne, 2, 3, 0, 0);

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

    public function testSingleRankedStateFinished(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

//        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count(); $nr++) {
//            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $nr);
//            $pouleOne->getPlace($nr)->setCompetitor($competitor);
//        }

        $this->setScoreSingle($pouleOne, 1, 2, 2, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 3, 2);

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

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setScoreSingle($pouleOne, 1, 2, 2, 1, State::InProgress);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1, State::InProgress);
        $this->setScoreSingle($pouleOne, 2, 3, 3, 2, State::InProgress);

        $roundRankingCalculator = new RoundRankingCalculator([State::InProgress,State::Finished]);
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

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6);
        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);

        $this->setScoreSingle($pouleOne, 1, 2, 2, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 3, 2);

        $this->setScoreSingle($pouleTwo, 1, 2, 4, 2);
        $this->setScoreSingle($pouleTwo, 1, 3, 6, 2);
        $this->setScoreSingle($pouleTwo, 2, 3, 6, 4);

        $roundRankingCalculator = new RoundRankingCalculator();
        $firstHorizontalPoule = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 1);
        self::assertInstanceOf(HorizontalPoule::class, $firstHorizontalPoule);
        $placeLocations = $roundRankingCalculator->getPlaceLocationsForHorizontalPoule($firstHorizontalPoule);

        self::assertSame($placeLocations[0]->getPouleNr(), 2);
        self::assertSame($placeLocations[1]->getPouleNr(), 1);

        $competition->setRankingRuleSet(RankingRuleSet::AgainstAmong);
        $roundRankingCalculator2 = new RoundRankingCalculator();
        $placeLocations2 = $roundRankingCalculator2->getPlaceLocationsForHorizontalPoule($firstHorizontalPoule);

        self::assertSame($placeLocations2[0]->getPouleNr(), 2);
        self::assertSame($placeLocations2[1]->getPouleNr(), 1);
    }

    public function testHorizontalRankedNoSingleRule(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);

        $this->setScoreSingle($pouleOne, 1, 2, 2, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 3, 2);

        $this->setScoreSingle($pouleTwo, 1, 2, 4, 2);
        $this->setScoreSingle($pouleTwo, 1, 3, 6, 2);
        $this->setScoreSingle($pouleTwo, 2, 3, 6, 4);

        $roundRankingCalculator = new RoundRankingCalculator();
        $firstHorizontalPoule = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 1);
        self::assertInstanceOf(HorizontalPoule::class, $firstHorizontalPoule);
        $placeLocations = $roundRankingCalculator->getPlaceLocationsForHorizontalPoule($firstHorizontalPoule);

        self::assertSame(count($placeLocations), 0);
    }

    public function testSingleRankedECWC(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 4);
        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setScoreSingle($pouleOne, 1, 2, 1, 0);
        $this->setScoreSingle($pouleOne, 1, 3, 1, 0);
        $this->setScoreSingle($pouleOne, 1, 4, 0, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 2, 0);
        $this->setScoreSingle($pouleOne, 2, 4, 1, 0);
        $this->setScoreSingle($pouleOne, 3, 4, 1, 0);

        $roundRankingCalculator = new RoundRankingCalculator();
        $items = $roundRankingCalculator->getItemsForPoule($pouleOne);
        $roundRankingItem1 = $roundRankingCalculator->getItemByRank($items, 1);
        self::assertNotNull($roundRankingItem1);
        $roundRankingItem2 = $roundRankingCalculator->getItemByRank($items, 2);
        self::assertNotNull($roundRankingItem2);
        self::assertSame($roundRankingItem1->getPlace(), $pouleOne->getPlace(2));
        self::assertSame($roundRankingItem2->getPlace(), $pouleOne->getPlace(1));

        $competition->setRankingRuleSet(RankingRuleSet::AgainstAmong);
        $roundRankingCalculatorAmong = new RoundRankingCalculator();
        $itemsEC = $roundRankingCalculatorAmong->getItemsForPoule($pouleOne);

        $roundRankingItemEC1 = $roundRankingCalculatorAmong->getItemByRank($itemsEC, 1);
        self::assertNotNull($roundRankingItemEC1);
        $roundRankingItemEC2 = $roundRankingCalculatorAmong->getItemByRank($itemsEC, 2);
        self::assertNotNull($roundRankingItemEC2);
        self::assertSame($roundRankingItemEC1->getPlace(), $pouleOne->getPlace(1));
        self::assertSame($roundRankingItemEC2->getPlace(), $pouleOne->getPlace(2));
    }

    public function testVariation1MostPoints(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setScoreSingle($pouleOne, 1, 2, 1, 2);
        $this->setScoreSingle($pouleOne, 1, 3, 1, 3);
        $this->setScoreSingle($pouleOne, 2, 3, 2, 3);

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

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 4);
        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setScoreSingle($pouleOne, 1, 2, 5, 0);
        $this->setScoreSingle($pouleOne, 1, 3, 0, 1);
        $this->setScoreSingle($pouleOne, 1, 4, 1, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 0, 0);
        // $this->setScoreSingle(pouleOne, 2, 4, 0, 1);
        $this->setScoreSingle($pouleOne, 3, 4, 0, 1);

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

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 4);
        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        // $this->setScoreSingle($pouleOne, 1, 2, 1, 0);
        $this->setScoreSingle($pouleOne, 1, 3, 1, 0);
        $this->setScoreSingle($pouleOne, 1, 4, 1, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 0, 0);
        $this->setScoreSingle($pouleOne, 2, 4, 0, 5);
        $this->setScoreSingle($pouleOne, 3, 4, 3, 0);

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

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setScoreSingle($pouleOne, 1, 2, 1, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 2, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 1, 0);

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

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 4);
        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        // 3 gelijk laten eindigen
        $this->setScoreSingle($pouleOne, 1, 2, 1, 0);
        // setScoreSingle(pouleOne, 1, 3, 1, 0);
        // setScoreSingle(pouleOne, 1, 4, 1, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 0, 1);
        $this->setScoreSingle($pouleOne, 2, 4, 0, 1);
        // setScoreSingle(pouleOne, 3, 4, 3, 0);

        $roundRankingCalculator = new RoundRankingCalculator();
        $items = $roundRankingCalculator->getItemsForPoule($pouleOne);

        $roundRankingItem = $roundRankingCalculator->getItemByRank($items, 4);
        self::assertNotNull($roundRankingItem);
        self::assertSame($roundRankingItem->getPlace(), $pouleOne->getPlace(2));
    }

    public function testVariation5AgainstEachOtherEqual(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 4);
        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        // 3 gelijk laten eindigen
        $this->setScoreSingle($pouleOne, 1, 2, 1, 0);
        $this->setScoreSingle($pouleOne, 1, 3, 1, 0);
        $this->setScoreSingle($pouleOne, 1, 4, 0, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 0, 1);
        $this->setScoreSingle($pouleOne, 2, 4, 0, 1);
        $this->setScoreSingle($pouleOne, 3, 4, 1, 0);

        $roundRankingCalculator = new RoundRankingCalculator();
        $items = $roundRankingCalculator->getItemsForPoule($pouleOne);

        self::assertSame($items[0]->getRank(), 1);
        self::assertSame($items[1]->getRank(), 1);
        self::assertSame($items[2]->getRank(), 1);
        $roundRankingItem = $roundRankingCalculator->getItemByRank($items, 4);
        self::assertNotNull($roundRankingItem);
        self::assertSame($roundRankingItem->getPlace(), $pouleOne->getPlace(2));
    }
}
