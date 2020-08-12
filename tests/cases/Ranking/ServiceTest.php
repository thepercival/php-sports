<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 12-6-19
 * Time: 15:13
 */

namespace Sports\Tests\Ranking;

use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\SetScores;
use Sports\Structure\Service as StructureService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Ranking\Service as RankingService;
use Sports\State;
use Sports\Planning\Service as PlanningService;
use Sports\Competitor;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, GamesCreator, SetScores;

    public function testRuleDescriptions()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $ruleDescriptions = $rankingService->getRuleDescriptions();
        self::assertSame(count($ruleDescriptions), 5);

        $rankingService2 = new RankingService($rootRound, RankingService::RULESSET_EC);
        $ruleDescriptions2 = $rankingService2->getRuleDescriptions();
        self::assertSame(count($ruleDescriptions2), 5);


        $rankingService3 = new RankingService($rootRound, 0);
        $this->expectException(\Exception::class);
        $rankingService3->getRuleDescriptions();
    }

    public function testMultipleEqualRanked()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setScoreSingle($pouleOne, 1, 2, 0, 0);
        $this->setScoreSingle($pouleOne, 1, 3, 0, 0);
        $this->setScoreSingle($pouleOne, 2, 3, 0, 0);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);
        foreach ($items as $item) {
            self::assertSame($item->getRank(), 1);
        }

        // cached items
        $cachedItems = $rankingService->getItemsForPoule($pouleOne);
        foreach ($cachedItems as $item) {
            self::assertSame($item->getRank(), 1);
        }
    }

    public function testSingleRankedStateFinished()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count(); $nr++) {
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $nr);
            $pouleOne->getPlace($nr)->setCompetitor($competitor);
        }

        $this->setScoreSingle($pouleOne, 1, 2, 2, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 3, 2);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);

        self::assertSame($rankingService->getItemByRank($items, 1)->getPlace(), $pouleOne->getPlace(1));
        self::assertSame($rankingService->getItemByRank($items, 2)->getPlace(), $pouleOne->getPlace(2));
        self::assertSame($rankingService->getItemByRank($items, 3)->getPlace(), $pouleOne->getPlace(3));
    }

    public function testSingleRankedStateInProgressAndFinished()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setScoreSingle($pouleOne, 1, 2, 2, 1, State::InProgress);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1, State::InProgress);
        $this->setScoreSingle($pouleOne, 2, 3, 3, 2, State::InProgress);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC, State::InProgress + State::Finished);
        $items = $rankingService->getItemsForPoule($pouleOne);

        self::assertSame($rankingService->getItemByRank($items, 1)->getPlace(), $pouleOne->getPlace(1));
        self::assertSame($rankingService->getItemByRank($items, 2)->getPlace(), $pouleOne->getPlace(2));
        self::assertSame($rankingService->getItemByRank($items, 3)->getPlace(), $pouleOne->getPlace(3));

        $rankingService2 = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items2 = $rankingService2->getItemsForPoule($pouleOne);
        foreach ($items2 as $item) {
            self::assertSame($item->getRank(), 1);
        }
    }

    public function testHorizontalRankedECWC()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6);
        $rootRound = $structure->getRootRound();

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);

        $this->setScoreSingle($pouleOne, 1, 2, 2, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 3, 2);

        $this->setScoreSingle($pouleTwo, 1, 2, 4, 2);
        $this->setScoreSingle($pouleTwo, 1, 3, 6, 2);
        $this->setScoreSingle($pouleTwo, 2, 3, 6, 4);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $firstHorizontalPoule = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 1);
        $placeLocations = $rankingService->getPlaceLocationsForHorizontalPoule($firstHorizontalPoule);

        self::assertSame($placeLocations[0]->getPouleNr(), 2);
        self::assertSame($placeLocations[1]->getPouleNr(), 1);

        $rankingService2 = new RankingService($rootRound, RankingService::RULESSET_EC);
        $placeLocations2 = $rankingService2->getPlaceLocationsForHorizontalPoule($firstHorizontalPoule);

        self::assertSame($placeLocations2[0]->getPouleNr(), 2);
        self::assertSame($placeLocations2[1]->getPouleNr(), 1);
    }

    public function testHorizontalRankedNoSingleRule()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);

        $this->setScoreSingle($pouleOne, 1, 2, 2, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 3, 2);

        $this->setScoreSingle($pouleTwo, 1, 2, 4, 2);
        $this->setScoreSingle($pouleTwo, 1, 3, 6, 2);
        $this->setScoreSingle($pouleTwo, 2, 3, 6, 4);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $firstHorizontalPoule = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 1);
        $placeLocations = $rankingService->getPlaceLocationsForHorizontalPoule($firstHorizontalPoule);

        self::assertSame(count($placeLocations), 0);
    }

    public function testGetCompetitor()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $placeOne = $pouleOne->getPlace(1);
        $competitor = new Competitor($competition->getLeague()->getAssociation(), 'test');
        $placeOne->setCompetitor($competitor);
        $placeTwo = $pouleOne->getPlace(2);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);

        self::assertSame($rankingService->getCompetitor($placeOne->getLocation()), $competitor);
        self::assertSame($rankingService->getCompetitor($placeTwo->getLocation()), null);
    }

    public function testSingleRankedECWC()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 4);
        $rootRound = $structure->getRootRound();

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setScoreSingle($pouleOne, 1, 2, 1, 0);
        $this->setScoreSingle($pouleOne, 1, 3, 1, 0);
        $this->setScoreSingle($pouleOne, 1, 4, 0, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 2, 0);
        $this->setScoreSingle($pouleOne, 2, 4, 1, 0);
        $this->setScoreSingle($pouleOne, 3, 4, 1, 0);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);

        self::assertSame($rankingService->getItemByRank($items, 1)->getPlace(), $pouleOne->getPlace(2));
        self::assertSame($rankingService->getItemByRank($items, 2)->getPlace(), $pouleOne->getPlace(1));

        $rankingServiceEC = new RankingService($rootRound, RankingService::RULESSET_EC);
        $itemsEC = $rankingServiceEC->getItemsForPoule($pouleOne);

        self::assertSame($rankingServiceEC->getItemByRank($itemsEC, 1)->getPlace(), $pouleOne->getPlace(1));
        self::assertSame($rankingServiceEC->getItemByRank($itemsEC, 2)->getPlace(), $pouleOne->getPlace(2));
    }

    public function testVariation1MostPoints()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setScoreSingle($pouleOne, 1, 2, 1, 2);
        $this->setScoreSingle($pouleOne, 1, 3, 1, 3);
        $this->setScoreSingle($pouleOne, 2, 3, 2, 3);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);

        self::assertSame($rankingService->getItemByRank($items, 1)->getPlace(), $pouleOne->getPlace(3));
        self::assertSame($rankingService->getItemByRank($items, 2)->getPlace(), $pouleOne->getPlace(2));
        self::assertSame($rankingService->getItemByRank($items, 3)->getPlace(), $pouleOne->getPlace(1));
    }

    public function testVariation2FewestGames()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 4);
        $rootRound = $structure->getRootRound();

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setScoreSingle($pouleOne, 1, 2, 5, 0);
        $this->setScoreSingle($pouleOne, 1, 3, 0, 1);
        $this->setScoreSingle($pouleOne, 1, 4, 1, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 0, 0);
        // $this->setScoreSingle(pouleOne, 2, 4, 0, 1);
        $this->setScoreSingle($pouleOne, 3, 4, 0, 1);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);

        self::assertSame($rankingService->getItemByRank($items, 1)->getPlace(), $pouleOne->getPlace(4));
        self::assertSame($rankingService->getItemByRank($items, 2)->getPlace(), $pouleOne->getPlace(1));
        self::assertSame($rankingService->getItemByRank($items, 3)->getPlace(), $pouleOne->getPlace(3));
    }

    public function testVariation3FewestGames()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 4);
        $rootRound = $structure->getRootRound();

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        // $this->setScoreSingle($pouleOne, 1, 2, 1, 0);
        $this->setScoreSingle($pouleOne, 1, 3, 1, 0);
        $this->setScoreSingle($pouleOne, 1, 4, 1, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 0, 0);
        $this->setScoreSingle($pouleOne, 2, 4, 0, 5);
        $this->setScoreSingle($pouleOne, 3, 4, 3, 0);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);

        self::assertSame($rankingService->getItemByRank($items, 1)->getPlace(), $pouleOne->getPlace(1));
        self::assertSame($rankingService->getItemByRank($items, 2)->getPlace(), $pouleOne->getPlace(4));
        self::assertSame($rankingService->getItemByRank($items, 3)->getPlace(), $pouleOne->getPlace(3));
    }

    public function testVariation4MostScored()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setScoreSingle($pouleOne, 1, 2, 1, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 2, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 1, 0);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);

        self::assertSame($rankingService->getItemByRank($items, 1)->getPlace(), $pouleOne->getPlace(1));
        self::assertSame($rankingService->getItemByRank($items, 2)->getPlace(), $pouleOne->getPlace(2));
        self::assertSame($rankingService->getItemByRank($items, 3)->getPlace(), $pouleOne->getPlace(3));
    }

    public function testVariation5AgainstEachOtherNoGames()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 4);
        $rootRound = $structure->getRootRound();

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        // 3 gelijk laten eindigen
        $this->setScoreSingle($pouleOne, 1, 2, 1, 0);
        // setScoreSingle(pouleOne, 1, 3, 1, 0);
        // setScoreSingle(pouleOne, 1, 4, 1, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 0, 1);
        $this->setScoreSingle($pouleOne, 2, 4, 0, 1);
        // setScoreSingle(pouleOne, 3, 4, 3, 0);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);

        self::assertSame($rankingService->getItemByRank($items, 4)->getPlace(), $pouleOne->getPlace(2));
    }

    public function testVariation5AgainstEachOtherEqual()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 4);
        $rootRound = $structure->getRootRound();

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        // 3 gelijk laten eindigen
        $this->setScoreSingle($pouleOne, 1, 2, 1, 0);
        $this->setScoreSingle($pouleOne, 1, 3, 1, 0);
        $this->setScoreSingle($pouleOne, 1, 4, 0, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 0, 1);
        $this->setScoreSingle($pouleOne, 2, 4, 0, 1);
        $this->setScoreSingle($pouleOne, 3, 4, 1, 0);

        $rankingService = new RankingService($rootRound, RankingService::RULESSET_WC);
        $items = $rankingService->getItemsForPoule($pouleOne);

        self::assertSame($items[0]->getRank(), 1);
        self::assertSame($items[1]->getRank(), 1);
        self::assertSame($items[2]->getRank(), 1);
        self::assertSame($rankingService->getItemByRank($items, 4)->getPlace(), $pouleOne->getPlace(2));
    }
}
