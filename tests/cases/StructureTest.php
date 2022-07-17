<?php

declare(strict_types=1);

namespace Sports\Tests;

use Sports\Category;
use Sports\Qualify\Target as QualifyTarget;
use PHPUnit\Framework\TestCase;
use Sports\Structure;
use Sports\Structure\Editor as StructureService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\StructureEditorCreator;
use SportsHelpers\PouleStructure;

class StructureTest extends TestCase
{
    use CompetitionCreator;
    use StructureEditorCreator;

    public function testBasics(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4,4,4,4]);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getSingleCategory()->getRootRound();

        self::assertSame($rootRound->getNumber(), $firstRoundNumber);

        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);

        self::assertSame($rootRound->getNumber()->getNext(), $structure->getLastRoundNumber());

        self::assertSame(count($structure->getRoundNumbers()), 2);

        self::assertSame($structure->getRoundNumber(1), $firstRoundNumber);
        self::assertSame($structure->getRoundNumber(2), $firstRoundNumber->getNext());
        self::assertSame($structure->getRoundNumber(3), null);
        self::assertSame($structure->getRoundNumber(0), null);
    }
}
