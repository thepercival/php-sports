<?php

declare(strict_types=1);

namespace Sports\Tests\Structure;

use Exception;
use PHPUnit\Framework\TestCase;
use Sports\Output\StructureOutput;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Structure;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\StructureEditorCreator;
use SportsHelpers\PlaceRanges;
use SportsHelpers\PouleStructure\Balanced as BalancedPouleStructure;
use SportsHelpers\Sport\Variant\MinNrOfPlacesCalculator;

final class OutputTest extends TestCase
{
    use CompetitionCreator;
    use StructureEditorCreator;

    public function testTwoCategoriesSimple(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5]);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $balancedPouleStructure = new BalancedPouleStructure(...[5]);
        $structureEditor->addCategory('dames', null, $firstRoundNumber, $balancedPouleStructure);

        $newStructure = new Structure(array_values($competition->getCategories()->toArray()), $firstRoundNumber);


        $categoryOne = $newStructure->getCategory(1);
        self::assertNotNull($categoryOne);
        $catOneRootRound = $categoryOne->getRootRound();

        $structureEditor->addChildRound($catOneRootRound, QualifyTarget::Winners, [2]);
        // (new StructureOutput())->output($newStructure);

        self::assertNull($newStructure->getRoundNumber(0));
    }

    public function testTwoCategoriesBig(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4,4,4,4]);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $balancedPouleStructure = new BalancedPouleStructure(...[4,4,4,4]);
        $damesCat = $structureEditor->addCategory('dames', null, $firstRoundNumber, $balancedPouleStructure);

        $damesSemFin = $structureEditor->addChildRound($damesCat->getRootRound(), QualifyTarget::Winners, [4]);
        $structureEditor->addChildRound($damesSemFin, QualifyTarget::Winners, [2]);

        $newStructure = new Structure(array_values($competition->getCategories()->toArray()), $firstRoundNumber);


        $categoryOne = $newStructure->getCategory(1);
        self::assertNotNull($categoryOne);
        // $catOneRootRound = $categoryOne->getRootRound();

        // $structureEditor->addChildRound($catOneRootRound, QualifyTarget::Winners, [2]);
        // (new StructureOutput())->output($newStructure);

        self::assertNull($newStructure->getRoundNumber(0));
    }

}
