<?php

declare(strict_types=1);

namespace Sports\Tests\Qualify;

use Sports\Competitor\StartLocation;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Output\StructureOutput;
use PHPUnit\Framework\TestCase;
use Sports\Team;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\SetScores;
use Sports\Qualify\Distribution as QualifyDistribution;
use Sports\Qualify\Service as QualifyService;
use Sports\Competitor\StartLocationMap;
use Sports\Qualify\Rule\Horizontal\Single as HorizontalSingleQualifyRule;
use Sports\Qualify\Rule\Horizontal\Multiple as HorizontalMultipleQualifyRule;
use Sports\Qualify\Group as QualifyGroup;
use Sports\TestHelper\StructureEditorCreator;
use SportsHelpers\PouleStructure;

final class ServiceTest extends TestCase
{
    use CompetitionCreator;
    use SetScores;
    use StructureEditorCreator;

    public function test2RoundNumbers5Places(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

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

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers();

        $competitors = [
            new TeamCompetitor(
                        $competition, new StartLocation(1, 1, 1),
                        new Team($competition->getLeague()->getAssociation(), 'tc 1.1') ),
            new TeamCompetitor(
                $competition, new StartLocation(1, 1, 2),
                new Team($competition->getLeague()->getAssociation(), 'tc 1.2') )
        ];

        $competitorMap = new StartLocationMap($competitors);

        $winnersPoule = $winnersRound->getPoule(1);

        // 1
        self::assertNotNull($winnersPoule->getPlace(1)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(1), $winnersPoule->getPlace(1)->getQualifiedPlace());


        $startLocation = $pouleOne->getPlace(1)->getStartLocation();
        self::assertNotNull($startLocation);
        $competitor = $competitorMap->getCompetitor($startLocation);
        self::assertNotNull($competitor);
        self::assertSame($competitor->getName(), 'tc 1.1');

        // 2
        self::assertNotNull($winnersPoule->getPlace(2)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(2), $winnersPoule->getPlace(2)->getQualifiedPlace());
        $startLocation = $pouleOne->getPlace(2)->getStartLocation();
        self::assertNotNull($startLocation);
        $competitor = $competitorMap->getCompetitor($startLocation);
        self::assertNotNull($competitor);
        self::assertSame($competitor->getName(), 'tc 1.2');

        $loserssPoule = $losersRound->getPoule(1);

        self::assertNotNull($loserssPoule->getPlace(1)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(4), $loserssPoule->getPlace(1)->getQualifiedPlace());
        self::assertNotNull($loserssPoule->getPlace(2)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(5), $loserssPoule->getPlace(2)->getQualifiedPlace());
    }

    public function test2RoundNumbers5PouleFilter(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3]);
        // $competitorMap = new CompetitorMap($this->createTeamCompetitors($competition, $firstRoundNumber));
        $rootRound = $structure->getSingleCategory()->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);
        // (new StructureOutput())->output($structure);
        $this->setAgainstScore($pouleOne, 1, 2, 2, 1);
        $this->setAgainstScore($pouleOne, 1, 3, 3, 1);
        $this->setAgainstScore($pouleOne, 2, 3, 4, 1);

        $this->setAgainstScore($pouleTwo, 1, 2, 2, 1);
        $this->setAgainstScore($pouleTwo, 1, 3, 3, 1);
        $this->setAgainstScore($pouleTwo, 2, 3, 4, 1);
        // 1: A1, B1
        // 2: A2, B3
        // 3: A2, B3

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers($pouleOne);

        $winnersPoule = $winnersRound->getPoule(1);

        self::assertNotNull($winnersPoule->getPlace(1)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(1), $winnersPoule->getPlace(1)->getQualifiedPlace());
        self::assertNull($winnersPoule->getPlace(2)->getQualifiedPlace());

        $loserssPoule = $losersRound->getPoule(1);

        // (new StructureOutput())->output($structure);

        self::assertNotNull($loserssPoule->getPlace(1)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(3), $loserssPoule->getPlace(1)->getQualifiedPlace());
        self::assertNull($loserssPoule->getPlace(2)->getQualifiedPlace());
    }

    public function test2RoundNumbers9Multiple(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3, 3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        // $competitorMap = new StartLocationMap(array_values( $competition->getTeamCompetitors()->toArray()));

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [4]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [4]);

        // W[4], L[4]

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);
        $pouleThree = $rootRound->getPoule(3);

        $this->setAgainstScore($pouleOne, 1, 2, 1, 2);
        $this->setAgainstScore($pouleOne, 1, 3, 1, 3);
        $this->setAgainstScore($pouleOne, 2, 3, 2, 3);
        $this->setAgainstScore($pouleTwo, 1, 2, 1, 2);
        $this->setAgainstScore($pouleTwo, 1, 3, 1, 3);
        $this->setAgainstScore($pouleTwo, 2, 3, 2, 4);
        $this->setAgainstScore($pouleThree, 1, 2, 1, 5);
        $this->setAgainstScore($pouleThree, 1, 3, 1, 3);
        $this->setAgainstScore($pouleThree, 2, 3, 2, 5);
        // Rank W 3.3, 2.3, 1.3, 3.2
        // Dropouts  2.2
        // Rank L 1,2, 3.3, 2.3, 1.3

        $qualifyService = new QualifyService($rootRound);
        $changedPlaces = $qualifyService->setQualifiers();
        self::assertSame(count($changedPlaces), 8);

        // (new StructureOutput())->output($structure);

        // winners
        $winnersPoule = $winnersRound->getPoule(1);
        $winnersQualifyGroup = $winnersRound->getParentQualifyGroup();
        self::assertNotNull($winnersQualifyGroup);

        $winnersPlace1 = $winnersPoule->getPlace(1);
        $qualifyRule = $winnersQualifyGroup->getRuleByToPlace($winnersPlace1);
        self::assertInstanceOf(HorizontalSingleQualifyRule::class, $qualifyRule);
        $winnersLocation1 = $winnersPlace1->getStartLocation();
        self::assertNotNull($winnersLocation1);
        // self::assertNotNull($competitorMap->getCompetitor($winnersLocation1));

        $winnersPlace2 = $winnersPoule->getPlace(2);
        $qualifyRule = $winnersQualifyGroup->getRuleByToPlace($winnersPlace2);
        self::assertInstanceOf(HorizontalSingleQualifyRule::class, $qualifyRule);

        $winnersPlace3 = $winnersPoule->getPlace(3);
        $qualifyRule = $winnersQualifyGroup->getRuleByToPlace($winnersPlace3);
        self::assertInstanceOf(HorizontalSingleQualifyRule::class, $qualifyRule);

        $winnersPlace4 = $winnersPoule->getPlace(4);
        $qualifyRule = $winnersQualifyGroup->getRuleByToPlace($winnersPlace4);
        self::assertInstanceOf(HorizontalMultipleQualifyRule::class, $qualifyRule);

        self::assertNotNull($winnersPlace1->getQualifiedPlace());
        self::assertNotNull($winnersPlace2->getQualifiedPlace());
        self::assertNotNull($winnersPlace3->getQualifiedPlace());

        self::assertSame($pouleThree->getPlace(2), $winnersPoule->getPlace(4)->getQualifiedPlace());

        // losers
        $losersQualifyGroup = $losersRound->getParentQualifyGroup();
        self::assertNotNull($losersQualifyGroup);
        $losersPoule = $losersRound->getPoule(1);

        $losersPlace1 = $losersPoule->getPlace(1);
        $qualifyRule = $losersQualifyGroup->getRuleByToPlace($losersPlace1);
        self::assertInstanceOf(HorizontalMultipleQualifyRule::class, $qualifyRule);

        $losersPlace2 = $losersPoule->getPlace(2);
        $qualifyRule = $losersQualifyGroup->getRuleByToPlace($losersPlace2);
        self::assertInstanceOf(HorizontalSingleQualifyRule::class, $qualifyRule);

        $losersPlace3 = $losersPoule->getPlace(3);
        $qualifyRule = $losersQualifyGroup->getRuleByToPlace($losersPlace3);
        self::assertInstanceOf(HorizontalSingleQualifyRule::class, $qualifyRule);

        $losersPlace4 = $losersPoule->getPlace(4);
        $qualifyRule = $losersQualifyGroup->getRuleByToPlace($losersPlace4);
        self::assertInstanceOf(HorizontalSingleQualifyRule::class, $qualifyRule);

        self::assertNotNull($losersPlace1->getQualifiedPlace());
        self::assertNotNull($losersPlace2->getQualifiedPlace());
        self::assertNotNull($losersPlace3->getQualifiedPlace());
        self::assertNotNull($losersPlace4->getQualifiedPlace());
    }

    public function test2RoundNumbers9MultipleNotFinished(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3, 3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [4]);

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);
        $pouleThree = $rootRound->getPoule(3);

        $this->setAgainstScore($pouleOne, 1, 2, 1, 2);
        $this->setAgainstScore($pouleOne, 1, 3, 1, 3);
        $this->setAgainstScore($pouleOne, 2, 3, 2, 3);
        $this->setAgainstScore($pouleTwo, 1, 2, 1, 2);
        $this->setAgainstScore($pouleTwo, 1, 3, 1, 3);
        $this->setAgainstScore($pouleTwo, 2, 3, 2, 4);
        $this->setAgainstScore($pouleThree, 1, 2, 1, 5);
        $this->setAgainstScore($pouleThree, 1, 3, 1, 3);
        // $this->setAgainstScore(pouleThree, 2, 3, 2, 5);

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers();

        $winnersPoule = $winnersRound->getPoule(1);

        self::assertNull($winnersPoule->getPlace(4)->getQualifiedPlace());
    }

    /**
     * When second place is multiple and both second places are ranked completely equal
     */
    public function testSameWinnersLosers(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [3]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [3]);

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);

        $this->setAgainstScore($pouleOne, 1, 2, 1, 0);
        $this->setAgainstScore($pouleOne, 3, 1, 0, 1);
        $this->setAgainstScore($pouleOne, 2, 3, 1, 0);
        $this->setAgainstScore($pouleTwo, 1, 2, 1, 0);
        $this->setAgainstScore($pouleTwo, 3, 1, 0, 1);
        $this->setAgainstScore($pouleTwo, 2, 3, 1, 0);

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers();

        $winnersPoule = $winnersRound->getPoule(1);

        self::assertNotNull($winnersPoule->getPlace(3)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(2), $winnersPoule->getPlace(3)->getQualifiedPlace());

        $loserssPoule = $losersRound->getPoule(1);
        self::assertNotNull($loserssPoule->getPlace(1)->getQualifiedPlace());
        self::assertSame($pouleTwo->getPlace(2), $loserssPoule->getPlace(1)->getQualifiedPlace());
    }

    public function testResetNextRound(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $winnersPoule = $winnersRound->getPoule(1);
        $bestFinalist = $winnersPoule->getPlace(1);
        $bestFinalist->setExtraPoints(-1);

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setAgainstScore($pouleOne, 1, 2, 1, 0);
        $this->setAgainstScore($pouleOne, 1, 3, 1, 0);
        $this->setAgainstScore($pouleOne, 2, 3, 1, 0);

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers();

        self::assertNotNull($bestFinalist->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(1), $bestFinalist->getQualifiedPlace());
        self::assertSame(-1, $bestFinalist->getExtraPoints());

        $qualifyService->resetQualifiers();
        /** @psalm-suppress DocblockTypeContradiction */
        self::assertSame(0, $bestFinalist->getExtraPoints());
    }

    /**
     * When second place is multiple and both second places are ranked completely equal
     */
    public function testSameWinnerslosers2ndPlaceMultipleRule(): void {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        // $competitorMap = new StartLocationMap(array_values( $competition->getTeamCompetitors()->toArray()));

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [3]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [3]);

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setAgainstScore($pouleOne, 1, 2, 1, 0);
        $this->setAgainstScore($pouleOne, 3, 1, 0, 1);
        $this->setAgainstScore($pouleOne, 2, 3, 1, 0);
        $this->setAgainstScore($pouleOne, 1, 2, 1, 0);
        $this->setAgainstScore($pouleOne, 3, 1, 0, 1);
        $this->setAgainstScore($pouleOne, 2, 3, 1, 0);

        $pouleTwo = $rootRound->getPoule(2);

        $this->setAgainstScore($pouleTwo, 1, 2, 1, 0);
        $this->setAgainstScore($pouleTwo, 3, 1, 0, 1);
        $this->setAgainstScore($pouleTwo, 2, 3, 1, 0);
        $this->setAgainstScore($pouleTwo, 1, 2, 1, 0);
        $this->setAgainstScore($pouleTwo, 3, 1, 0, 1);
        $this->setAgainstScore($pouleTwo, 2, 3, 1, 0);

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers();

//        (new StructureOutput())->output($structure);

        $winnersPoule = $winnersRound->getPoule(1);
        $winnersPlace3 = $winnersPoule->getPlace(3);
        $winnersLocation3 = $winnersPlace3->getStartLocation();
        if ($winnersLocation3 !== null) {
            self::assertEquals(1, $winnersLocation3->getPouleNr());
            self::assertEquals(2, $winnersLocation3->getPlaceNr());
        }

        $losersPoule = $losersRound->getPoule(1);
        $losersPlace1 = $losersPoule->getPlace(1);
        $losersLocation1 = $losersPlace1->getStartLocation();
        if ($losersLocation1 !== null ) {
            self::assertEquals(2, $losersLocation1->getPouleNr());
            self::assertEquals(2, $losersLocation1->getPlaceNr());
        }
    }


    /**
     * When second place is multiple and both second places are ranked completely equal
     */
    public function testQualifyWithVerticalDistribution(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [4,4,4], QualifyDistribution::Vertical);

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);
        $pouleThree = $rootRound->getPoule(3);
        //        (new StructureOutput())->output($structure);

        $this->setAgainstScore($pouleOne, 1, 2, 1, 0);
        $this->setAgainstScore($pouleOne, 3, 4, 1, 0);
        $this->setAgainstScore($pouleOne, 3, 1, 0, 1);
        $this->setAgainstScore($pouleOne, 2, 4, 1, 0);
        $this->setAgainstScore($pouleOne, 2, 3, 1, 0);
        $this->setAgainstScore($pouleOne, 4, 1, 0, 1);

        $this->setAgainstScore($pouleTwo, 1, 2, 2, 0);
        $this->setAgainstScore($pouleTwo, 3, 4, 2, 0);
        $this->setAgainstScore($pouleTwo, 3, 1, 0, 2);
        $this->setAgainstScore($pouleTwo, 2, 4, 2, 0);
        $this->setAgainstScore($pouleTwo, 2, 3, 2, 0);
        $this->setAgainstScore($pouleTwo, 4, 1, 0, 2);

        $this->setAgainstScore($pouleThree, 1, 2, 3, 0);
        $this->setAgainstScore($pouleThree, 3, 4, 3, 0);
        $this->setAgainstScore($pouleThree, 3, 1, 0, 3);
        $this->setAgainstScore($pouleThree, 2, 4, 3, 0);
        $this->setAgainstScore($pouleThree, 2, 3, 3, 0);
        $this->setAgainstScore($pouleThree, 4, 1, 0, 3);

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers();

//        (new StructureOutput())->output($structure);

        // nr 4 van nrs-1-poule is nr 2 van de poule A van de vorige ronde
        $winnersPoule1 = $winnersRound->getPoule(1);
        $winnersPlace4 = $winnersPoule1->getPlace(4);
        $winnersLocation4 = $winnersPlace4->getStartLocation();
        if ($winnersLocation4 !== null) {
            self::assertEquals(3, $winnersLocation4->getPouleNr());
            self::assertEquals(2, $winnersLocation4->getPlaceNr());
        }

        // nr 41van poule-3 is nr 3 van de 3e nr 3
        $winnersPoule3 = $winnersRound->getPoule(3);
        $winnersPlace1 = $winnersPoule3->getPlace(1);
        $winnersLocation1 = $winnersPlace1->getStartLocation();
        if ($winnersLocation1 !== null) {
            self::assertEquals(3, $winnersLocation1->getPouleNr());
            self::assertEquals(3, $winnersLocation1->getPlaceNr());
        }
    }
}
