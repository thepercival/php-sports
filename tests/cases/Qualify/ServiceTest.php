<?php
declare(strict_types=1);

namespace Sports\Tests\Qualify;

use Sports\Qualify\Target as QualifyTarget;
use Sports\Output\StructureOutput;
use PHPUnit\Framework\TestCase;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\SetScores;
use Sports\Structure\Editor as StructureService;
use Sports\Qualify\Service as QualifyService;
use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use Sports\Qualify\Group as QualifyGroup;
use Sports\TestHelper\StructureEditorCreator;
use SportsHelpers\PouleStructure;

class ServiceTest extends TestCase
{
    use CompetitionCreator, SetScores, StructureEditorCreator;

    public function test2RoundNumbers5(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5]);
        $rootRound = $structure->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [2]);

        (new GamesCreator())->createStructureGames($structure);

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

        $winnersPoule = $winnersRound->getPoule(1);

        self::assertNotNull($winnersPoule->getPlace(1)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(1), $winnersPoule->getPlace(1)->getQualifiedPlace());
        self::assertNotNull($winnersPoule->getPlace(2)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(2), $winnersPoule->getPlace(2)->getQualifiedPlace());

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
        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [2]);
        // (new StructureOutput())->output($structure);
        $this->setScoreSingle($pouleOne, 1, 2, 2, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 4, 1);

        $this->setScoreSingle($pouleTwo, 1, 2, 2, 1);
        $this->setScoreSingle($pouleTwo, 1, 3, 3, 1);
        $this->setScoreSingle($pouleTwo, 2, 3, 4, 1);
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

        self::assertNull($loserssPoule->getPlace(1)->getQualifiedPlace());
        self::assertNull($loserssPoule->getPlace(2)->getQualifiedPlace());
    }

    public function test2RoundNumbers9Multiple(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3,3]);
        $rootRound = $structure->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [4]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [4]);
        // (new StructureOutput())->output($structure);
        // W[4], L[4]

        (new GamesCreator())->createStructureGames($structure);

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

        // winners
        $winnersPoule = $winnersRound->getPoule(1);
        $winnersQualifyGroup = $winnersRound->getParentQualifyGroup();
        self::assertNotNull($winnersQualifyGroup);
        $winnersPlace1 = $winnersPoule->getPlace(1);
        $qualifyRuleW1 = $winnersQualifyGroup->getRule($winnersPlace1);
        self::assertInstanceOf(SingleQualifyRule::class, $qualifyRuleW1);
        $winnersPlace2 = $winnersPoule->getPlace(2);
        $qualifyRuleW2 = $winnersQualifyGroup->getRule($winnersPlace2);
        self::assertInstanceOf(SingleQualifyRule::class, $qualifyRuleW2);
        $winnersPlace3 = $winnersPoule->getPlace(3);
        $qualifyRuleW3 = $winnersQualifyGroup->getRule($winnersPlace3);
        self::assertInstanceOf(SingleQualifyRule::class, $qualifyRuleW3);
        $winnersPlace4 = $winnersPoule->getPlace(4);
        $qualifyRuleW4 = $winnersQualifyGroup->getRule($winnersPlace4);
        self::assertInstanceOf(MultipleQualifyRule::class, $qualifyRuleW4);

        self::assertNotNull($winnersPlace1->getQualifiedPlace());
        self::assertNotNull($winnersPlace2->getQualifiedPlace());
        self::assertNotNull($winnersPlace3->getQualifiedPlace());

        self::assertSame($pouleThree->getPlace(2), $winnersPoule->getPlace(4)->getQualifiedPlace());

        // losers
        $losersQualifyGroup = $losersRound->getParentQualifyGroup();
        self::assertNotNull($losersQualifyGroup);
        $losersPoule = $losersRound->getPoule(1);

        $losersPlace1 = $losersPoule->getPlace(1);
        $qualifyRuleL1 = $losersQualifyGroup->getRule($losersPlace1);
        self::assertInstanceOf(MultipleQualifyRule::class, $qualifyRuleL1);
        $losersPlace2 = $losersPoule->getPlace(2);
        $qualifyRuleL2 = $losersQualifyGroup->getRule($losersPlace2);
        self::assertInstanceOf(SingleQualifyRule::class, $qualifyRuleL2);
        $losersPlace3 = $losersPoule->getPlace(3);
        $qualifyRuleL3 = $losersQualifyGroup->getRule($losersPlace3);
        self::assertInstanceOf(SingleQualifyRule::class, $qualifyRuleL3);
        $losersPlace4 = $losersPoule->getPlace(4);
        $qualifyRuleL4 = $losersQualifyGroup->getRule($losersPlace4);
        self::assertInstanceOf(SingleQualifyRule::class, $qualifyRuleL4);

        self::assertNotNull($losersPlace1->getQualifiedPlace());
        self::assertNotNull($losersPlace2->getQualifiedPlace());
        self::assertNotNull($losersPlace3->getQualifiedPlace());
        self::assertNotNull($losersPlace4->getQualifiedPlace());
    }

    public function test2RoundNumbers9MultipleNotFinished(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3,3]);
        $rootRound = $structure->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [4]);

        (new GamesCreator())->createStructureGames($structure);

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

        self::assertNull($winnersPoule->getPlace(4)->getQualifiedPlace());
    }

    /**
     * When second place is multiple and both second places are ranked completely equal
     */
    public function testSameWinnersLosers(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3]);
        $rootRound = $structure->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [3]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [3]);

        (new GamesCreator())->createStructureGames($structure);

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

        $winnersPoule = $winnersRound->getPoule(1);

        self::assertNotNull($winnersPoule->getPlace(3)->getQualifiedPlace());
        self::assertSame($pouleOne->getPlace(2), $winnersPoule->getPlace(3)->getQualifiedPlace());

        $loserssPoule = $losersRound->getPoule(1);
        self::assertNotNull($loserssPoule->getPlace(1)->getQualifiedPlace());
        self::assertSame($pouleTwo->getPlace(2), $loserssPoule->getPlace(1)->getQualifiedPlace());
    }
}
