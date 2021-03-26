<?php
declare(strict_types=1);

namespace Sports\Tests\Qualify;

use Sports\Output\StructureOutput;
use PHPUnit\Framework\TestCase;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\SetScores;
use Sports\Structure\Service as StructureService;
use Sports\Qualify\Service as QualifyService;
use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use Sports\Qualify\Group as QualifyGroup;
use SportsHelpers\PouleStructure;

class ServiceTest extends TestCase
{
    use CompetitionCreator, SetScores;

    public function test2RoundNumbers5(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, new PouleStructure([5]));
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 2);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 2);

        (new GamesCreator())->createStructureGames( $structure );

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

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers();

        $winnersRound = $rootRound->getChild(QualifyGroup::WINNERS, 1);
        self::assertNotNull($winnersRound);
        $winnersPoule = $winnersRound->getPoule(1);

        self::assertNotNull($winnersPoule->getPlace(1)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(1), $winnersPoule->getPlace(1)->getQualifiedPlace() );
        self::assertNotNull($winnersPoule->getPlace(2)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(2), $winnersPoule->getPlace(2)->getQualifiedPlace() );


        $losersRound = $rootRound->getChild(QualifyGroup::LOSERS, 1);
        self::assertNotNull($losersRound);
        $loserssPoule = $losersRound->getPoule(1);

        self::assertNotNull($loserssPoule->getPlace(1)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(4), $loserssPoule->getPlace(1)->getQualifiedPlace());
        self::assertNotNull($loserssPoule->getPlace(2)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(5), $loserssPoule->getPlace(2)->getQualifiedPlace());
    }

    public function test2RoundNumbers5PouleFilter(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, new PouleStructure([3,3]));
        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames( $structure );

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

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers($pouleOne);

        $winnersRound = $rootRound->getChild(QualifyGroup::WINNERS, 1);
        self::assertNotNull($winnersRound);
        $winnersPoule = $winnersRound->getPoule(1);

        self::assertNotSame($winnersPoule->getPlace(1)->getQualifiedPlace(), null);
        self::assertSame($pouleOne->getPlace(1), $winnersPoule->getPlace(1)->getQualifiedPlace() );
        self::assertSame($winnersPoule->getPlace(2)->getQualifiedPlace(), null);

        $losersRound = $rootRound->getChild(QualifyGroup::LOSERS, 1);
        self::assertNotNull($losersRound);
        $loserssPoule = $losersRound->getPoule(1);

        self::assertSame($loserssPoule->getPlace(2)->getQualifiedPlace(), null);
        self::assertNotSame($loserssPoule->getPlace(1)->getQualifiedPlace(), null);
    }

    public function test2RoundNumbers9Multiple(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, new PouleStructure([3,3,3]));
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);
         $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 4);
        // W[2,2], L[2,2]

        $winnersRound = $rootRound->getChild(QualifyGroup::WINNERS, 1);
        self::assertNotNull($winnersRound);
        $losersRound = $rootRound->getChild(QualifyGroup::LOSERS, 1);
        self::assertNotNull($losersRound);

        (new StructureOutput())->output($structure);

        $structureService->removePoule($winnersRound);
        $structureService->removePoule($losersRound);
        // W[4], L[4]

        (new GamesCreator())->createStructureGames( $structure );

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
        // Rank W 3.3, 2.3, 1.3, 3.2
        // Dropouts  2.2
        // Rank L 1,2, 3.3, 2.3, 1.3

        $qualifyService = new QualifyService($rootRound);
        $changedPlaces = $qualifyService->setQualifiers();
        self::assertSame(count($changedPlaces), 8);

        $winnersPoule = $winnersRound->getPoule(1);

        self::assertInstanceOf(SingleQualifyRule::class, $winnersPoule->getPlace(1)->getFromQualifyRule());
        self::assertInstanceOf(SingleQualifyRule::class, $winnersPoule->getPlace(2)->getFromQualifyRule());
        self::assertInstanceOf(SingleQualifyRule::class, $winnersPoule->getPlace(3)->getFromQualifyRule());
        self::assertInstanceOf(MultipleQualifyRule::class, $winnersPoule->getPlace(4)->getFromQualifyRule());

        self::assertNotNull($winnersPoule->getPlace(1)->getQualifiedPlace());
        self::assertNotNull($winnersPoule->getPlace(2)->getQualifiedPlace());
        self::assertNotNull($winnersPoule->getPlace(3)->getQualifiedPlace());

        self::assertSame($pouleThree->getPlace(2), $winnersPoule->getPlace(4)->getQualifiedPlace());

        $losersPoule = $losersRound->getPoule(1);

        self::assertInstanceOf(MultipleQualifyRule::class, $losersPoule->getPlace(1)->getFromQualifyRule());
        self::assertInstanceOf(SingleQualifyRule::class, $losersPoule->getPlace(2)->getFromQualifyRule());
        self::assertInstanceOf(SingleQualifyRule::class, $losersPoule->getPlace(3)->getFromQualifyRule());
        self::assertInstanceOf(SingleQualifyRule::class, $losersPoule->getPlace(4)->getFromQualifyRule());

        self::assertNotNull($losersPoule->getPlace(1)->getQualifiedPlace());
        self::assertNotNull($losersPoule->getPlace(2)->getQualifiedPlace());
        self::assertNotNull($losersPoule->getPlace(3)->getQualifiedPlace());
        self::assertNotNull($losersPoule->getPlace(4)->getQualifiedPlace());
    }

    public function test2RoundNumbers9MultipleNotFinished(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, new PouleStructure([3,3,3]));
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);
        $winnersRound = $rootRound->getChild(QualifyGroup::WINNERS, 1);
        self::assertNotNull($winnersRound);
        $structureService->removePoule($winnersRound);

        (new GamesCreator())->createStructureGames( $structure );

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

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers();

        $winnersPoule = $winnersRound->getPoule(1);

        self::assertNull($winnersPoule->getPlace(4)->getQualifiedPlace() );
    }

    /**
     * When second place is multiple and both second places are ranked completely equal
     */
    public function testSameWinnersLosers(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, new PouleStructure([3,3]));
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 3);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 3);

        (new GamesCreator())->createStructureGames( $structure );

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);

        $this->setScoreSingle($pouleOne, 1, 2, 1, 0);
        $this->setScoreSingle($pouleOne, 3, 1, 0, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 1, 0);
        $this->setScoreSingle($pouleTwo, 1, 2, 1, 0);
        $this->setScoreSingle($pouleTwo, 3, 1, 0, 1);
        $this->setScoreSingle($pouleTwo, 2, 3, 1, 0);

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers();

        $winnersRound = $rootRound->getChild(QualifyGroup::WINNERS, 1);
        self::assertNotNull($winnersRound);
        $winnersPoule = $winnersRound->getPoule(1);

        self::assertNotNull($winnersPoule->getPlace(3)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(2), $winnersPoule->getPlace(3)->getQualifiedPlace());

        $losersRound = $rootRound->getChild(QualifyGroup::LOSERS, 1);
        self::assertNotNull($losersRound);
        $loserssPoule = $losersRound->getPoule(1);
        self::assertNotNull($loserssPoule->getPlace(1)->getQualifiedPlace() );
        self::assertSame($pouleTwo->getPlace(2), $loserssPoule->getPlace(1)->getQualifiedPlace());
    }

}
