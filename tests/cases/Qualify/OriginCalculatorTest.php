<?php
declare(strict_types=1);

namespace Sports\Tests\Qualify;

use Sports\Qualify\Target as QualifyTarget;
use Sports\Output\StructureOutput;
use PHPUnit\Framework\TestCase;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\SetScores;
use Sports\TestHelper\StructureEditorCreator;
use Sports\Qualify\OriginCalculator as QualifyOriginCalculator;

class OriginCalculatorTest extends TestCase
{
    use CompetitionCreator, SetScores, StructureEditorCreator;

    public function testNoHistory(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [2, 2, 2, 2, 2, 2, 2, 2]);
        $rootRound = $structure->getRootRound();

        $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
    
        $originCalculator = new QualifyOriginCalculator();
    
        $origins = $originCalculator->getPossibleOrigins($rootRound->getPoule(1));
        self::assertCount(0, $origins);
    }

    public function testMultipleRule(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [2, 2, 2, 2, 2, 2, 2, 2]);
        $rootRound = $structure->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);

        $winnersPoule = $winnersRound->getFirstPoule();

        $originCalculator = new QualifyOriginCalculator();
        $origins = $originCalculator->getPossibleOrigins($winnersPoule);
        // (new StructureOutput())->output($structure);
        self::assertCount(8, $origins);
    }

    // winners->depth 1
    public function testSingleRule1(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4, 4]);
        $rootRound = $structure->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2, 2, 2, 2]);

        $originCalculator = new QualifyOriginCalculator();

        $firstPouleWinnersRound = $winnersRound->getPoule(1);
        $firstWinnerOrigins = $originCalculator->getPossibleOrigins($firstPouleWinnersRound);
        self::assertCount(2, $firstWinnerOrigins);

        $lastPouleWinnersRound = $winnersRound->getPoule(4);
        $lastWinnerOrigins = $originCalculator->getPossibleOrigins($lastPouleWinnersRound);
        self::assertCount(2, $lastWinnerOrigins);

        // (new StructureOutput())->output(structure, console);
    }

    // winners->depth 1
    public function testSingleRule2(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4, 4]);
        $rootRound = $structure->getRootRound();

        $winnersChildRound = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [4, 4]);

        $winnersWinnersRound = $structureEditor->addChildRound($winnersChildRound, QualifyTarget::WINNERS, [2, 2]);

        // (new StructureOutput())->output(structure, console);

        $originCalculator = new QualifyOriginCalculator();

        $firstPouleWinnersWinnersRound = $winnersWinnersRound->getPoule(1);
        $origins = $originCalculator->getPossibleOrigins($firstPouleWinnersWinnersRound);
        self::assertCount(6, $origins); // 4 from round 1 and 2 from round 2
    }
}
