<?php

namespace Sports\Tests\Qualify;

use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\SetScores;
use Sports\Structure\Service as StructureService;
use Sports\Qualify\Service as QualifyService;
use Sports\Ranking\Service as RankingService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Competitor;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, GamesCreator, SetScores;

    public function test2RoundNumbers5()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 2);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 2);

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);

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

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers();

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);

        self::assertNotNull($winnersPoule->getPlace(1)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(1), $winnersPoule->getPlace(1)->getQualifiedPlace() );
        self::assertNotNull($winnersPoule->getPlace(2)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(2), $winnersPoule->getPlace(2)->getQualifiedPlace() );


        $loserssPoule = $rootRound->getChild(QualifyGroup::LOSERS, 1)->getPoule(1);

        self::assertNotNull($loserssPoule->getPlace(1)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(4), $loserssPoule->getPlace(1)->getQualifiedPlace());
        self::assertNotNull($loserssPoule->getPlace(2)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(5), $loserssPoule->getPlace(2)->getQualifiedPlace());
    }

    public function test2RoundNumbers5PouleFilter()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6);
        $rootRound = $structure->getRootRound();

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);


        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 2);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 2);

        $this->setScoreSingle($pouleOne, 1, 2, 2, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 4, 1);

        $this->setScoreSingle($pouleTwo, 1, 2, 2, 1);
        $this->setScoreSingle($pouleTwo, 1, 3, 3, 1);
        $this->setScoreSingle($pouleTwo, 2, 3, 4, 1);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers($pouleOne);

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);

        self::assertNotSame($winnersPoule->getPlace(1)->getQualifiedPlace(), null);
        self::assertSame($pouleOne->getPlace(1), $winnersPoule->getPlace(1)->getQualifiedPlace() );
        self::assertSame($winnersPoule->getPlace(2)->getQualifiedPlace(), null);

        $loserssPoule = $rootRound->getChild(QualifyGroup::LOSERS, 1)->getPoule(1);

        self::assertSame($loserssPoule->getPlace(2)->getQualifiedPlace(), null);
        self::assertNotSame($loserssPoule->getPlace(1)->getQualifiedPlace(), null);
    }

    public function test2RoundNumbers9Multiple()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 9);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 4);

        $structureService->removePoule($rootRound->getChild(QualifyGroup::WINNERS, 1));
        $structureService->removePoule($rootRound->getChild(QualifyGroup::LOSERS, 1));

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);
        $pouleThree = $rootRound->getPoule(3);

        $this->setScoreSingle($pouleOne, 1, 2, 1, 2);
        $this->setScoreSingle($pouleOne, 1, 3, 1, 3);
        $this->setScoreSingle($pouleOne, 2, 3, 2, 3);
        $this->setScoreSingle($pouleTwo, 1, 2, 1, 2);
        $this->setScoreSingle($pouleTwo, 1, 3, 1, 3);
        $this->setScoreSingle($pouleTwo, 2, 3, 2, 4);
        $this->setScoreSingle($pouleThree, 1, 2, 1, 5);
        $this->setScoreSingle($pouleThree, 1, 3, 1, 3);
        $this->setScoreSingle($pouleThree, 2, 3, 2, 5);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $changedPlaces = $qualifyService->setQualifiers();
        self::assertSame(count($changedPlaces), 8);

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);

        self::assertSame($winnersPoule->getPlace(1)->getFromQualifyRule()->isSingle(), true);
        self::assertNotNull($winnersPoule->getPlace(1)->getQualifiedPlace());
        self::assertSame($winnersPoule->getPlace(2)->getFromQualifyRule()->isSingle(), true);
        self::assertNotNull($winnersPoule->getPlace(2)->getQualifiedPlace());
        self::assertSame($winnersPoule->getPlace(3)->getFromQualifyRule()->isSingle(), true);
        self::assertNotNull($winnersPoule->getPlace(3)->getQualifiedPlace());
        self::assertSame($winnersPoule->getPlace(4)->getFromQualifyRule()->isMultiple(), true);
        self::assertSame($pouleThree->getPlace(2), $winnersPoule->getPlace(4)->getQualifiedPlace());

        $losersPoule = $rootRound->getChild(QualifyGroup::LOSERS, 1)->getPoule(1);

        self::assertSame($losersPoule->getPlace(1)->getFromQualifyRule()->isMultiple(), true);
        self::assertNotNull($losersPoule->getPlace(1)->getQualifiedPlace());
        self::assertSame($losersPoule->getPlace(2)->getFromQualifyRule()->isSingle(), true);
        self::assertNotNull($losersPoule->getPlace(2)->getQualifiedPlace());
        self::assertTrue($losersPoule->getPlace(3)->getFromQualifyRule()->isSingle());
        self::assertNotNull($losersPoule->getPlace(3)->getQualifiedPlace());
        self::assertTrue($losersPoule->getPlace(4)->getFromQualifyRule()->isSingle());
        self::assertNotNull($losersPoule->getPlace(4)->getQualifiedPlace());
    }

    public function test2RoundNumbers9MultipleNotFinished()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 9);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);
        $structureService->removePoule($rootRound->getChild(QualifyGroup::WINNERS, 1));

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);
        $pouleThree = $rootRound->getPoule(3);

        $this->setScoreSingle($pouleOne, 1, 2, 1, 2);
        $this->setScoreSingle($pouleOne, 1, 3, 1, 3);
        $this->setScoreSingle($pouleOne, 2, 3, 2, 3);
        $this->setScoreSingle($pouleTwo, 1, 2, 1, 2);
        $this->setScoreSingle($pouleTwo, 1, 3, 1, 3);
        $this->setScoreSingle($pouleTwo, 2, 3, 2, 4);
        $this->setScoreSingle($pouleThree, 1, 2, 1, 5);
        $this->setScoreSingle($pouleThree, 1, 3, 1, 3);
        // $this->setScoreSingle(pouleThree, 2, 3, 2, 5);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers();

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);

        self::assertNull($winnersPoule->getPlace(4)->getQualifiedPlace() );
    }

    /**
     * When second place is multiple and both second places are ranked completely equal
     */
    public function testSameWinnersLosers()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6,2);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 3);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 3);

        $this->createGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);

        $this->setScoreSingle($pouleOne, 1, 2, 1, 0);
        $this->setScoreSingle($pouleOne, 3, 1, 0, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 1, 0);
        $this->setScoreSingle($pouleTwo, 1, 2, 1, 0);
        $this->setScoreSingle($pouleTwo, 3, 1, 0, 1);
        $this->setScoreSingle($pouleTwo, 2, 3, 1, 0);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers();

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);

        self::assertNotNull($winnersPoule->getPlace(3)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(2), $winnersPoule->getPlace(3)->getQualifiedPlace());

        $loserssPoule = $rootRound->getChild(QualifyGroup::LOSERS, 1)->getPoule(1);
        self::assertNotNull($loserssPoule->getPlace(1)->getQualifiedPlace() );
        self::assertSame($pouleTwo->getPlace(2), $loserssPoule->getPlace(1)->getQualifiedPlace());
    }

}
