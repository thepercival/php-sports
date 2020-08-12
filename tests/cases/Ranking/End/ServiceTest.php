<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 13-6-2019
 * Time: 09:25
 */

namespace Sports\Tests\Ranking\End;

use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\SetScores;
use Sports\Structure\Service as StructureService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Service as QualifyService;
use Sports\Ranking\Service as RankingService;
use Sports\Ranking\End\Service as EndRankingService;
use Sports\Planning\Service as PlanningService;
use Sports\Competitor;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, GamesCreator, SetScores;

    public function testOnePouleOfThreePlaces()
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

        $endRankingService = new EndRankingService($structure, RankingService::RULESSET_WC);
        $items = $endRankingService->getItems();

        for ($rank = 1; $rank <= count($items); $rank++) {
            self::assertSame($items[$rank - 1]->getName(), '0' . $rank);
            self::assertSame($items[$rank - 1]->getUniqueRank(), $rank);
        }
    }

    public function testOnePouleOfThreePlacesWithNoCompetitor()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 3);
        $rootRound = $structure->getRootRound();

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $competitor1 = new Competitor($competition->getLeague()->getAssociation(), '01');
        $competitor2 = new Competitor($competition->getLeague()->getAssociation(), '02');
        $pouleOne->getPlace(1)->setCompetitor($competitor1);
        $pouleOne->getPlace(2)->setCompetitor($competitor2);

        $this->setScoreSingle($pouleOne, 1, 2, 2, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 3, 2);

        $endRankingService = new EndRankingService($structure, RankingService::RULESSET_WC);
        $items = $endRankingService->getItems();

        self::assertSame($items[2]->getName(), 'onbekend');
    }

    public function testOnePouleOfThreePlacesNotPlayed()
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
        // $this->setScoreSingle($pouleOne, 2, 3, 3, 2);

        $endRankingService = new EndRankingService($structure, RankingService::RULESSET_WC);
        $items = $endRankingService->getItems();

        for ($rank = 1; $rank <= count($items); $rank++) {
            self::assertSame($items[$rank - 1]->getName(), 'nog onbekend');
        }
    }

    public function testTwoRoundNumbers5()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
        $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        for ($nr = 1; $nr <= $pouleOne->getPlaces()->count(); $nr++) {
            $competitor = new Competitor($competition->getLeague()->getAssociation(), '0' . $nr);
            $pouleOne->getPlace($nr)->setCompetitor($competitor);
        }

        $this->setScoreSingle($pouleOne, 1, 2, 2, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1);
        $this->setScoreSingle($pouleOne, 1, 4, 4, 1);
        $this->setScoreSingle($pouleOne, 1, 5, 5, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 3, 2);
        $this->setScoreSingle($pouleOne, 2, 4, 4, 2);
        $this->setScoreSingle($pouleOne, 2, 5, 5, 2);
        $this->setScoreSingle($pouleOne, 3, 4, 4, 3);
        $this->setScoreSingle($pouleOne, 3, 5, 5, 3);
        $this->setScoreSingle($pouleOne, 4, 5, 5, 4);

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);
        $this->setScoreSingle($winnersPoule, 1, 2, 2, 1);
        $loserssPoule = $rootRound->getChild(QualifyGroup::LOSERS, 1)->getPoule(1);
        $this->setScoreSingle($loserssPoule, 1, 2, 2, 1);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers();

        $endRankingService = new EndRankingService($structure, RankingService::RULESSET_WC);
        $items = $endRankingService->getItems();

        for ($rank = 1; $rank <= count($items); $rank++) {
            self::assertSame($items[$rank - 1]->getName(), '0' . $rank);
        }
    }
}
