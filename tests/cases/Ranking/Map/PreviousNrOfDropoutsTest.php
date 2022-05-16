<?php

declare(strict_types=1);

namespace Sports\Tests\Ranking\Map;

use PHPUnit\Framework\TestCase;
use Sports\TestHelper\CompetitionCreator;
use Sports\Qualify\Target as QualifyTarget;
use Sports\TestHelper\StructureEditorCreator;
use Sports\Ranking\Map\PreviousNrOfDropouts as PreviousNrOfDropoutsMap;

final class PreviousNrOfDropoutsTest extends TestCase
{
    use CompetitionCreator;
    use StructureEditorCreator;

    // [7,7] => W[5],L[5] => W[2],L[2],W[2],L[2]
    public function testSimple(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [7, 7]);
        $rootRound = $this->getFirstCategory($structure)->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        self::assertSame($firstRoundNumber, $rootRound->getNumber());
        self::assertSame($firstRoundNumber, $structure->getLastRoundNumber());

        // (new StructureOutput())->output($structure, console);
        $winnersChildRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [5]);
        $losersChildRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [5]);

        $round1and2 = $structureEditor->addChildRound($winnersChildRound, QualifyTarget::Winners, [2]);
        $structureEditor->addChildRound($winnersChildRound, QualifyTarget::Losers, [2]);
        $round10and11 = $structureEditor->addChildRound($losersChildRound, QualifyTarget::Winners, [2]);
        $round13and14 = $structureEditor->addChildRound($losersChildRound, QualifyTarget::Losers, [2]);

        $previousDropoutsMap = new PreviousNrOfDropoutsMap($rootRound);
        self::assertSame(0, $previousDropoutsMap->get($round1and2));
        self::assertSame(9, $previousDropoutsMap->get($round10and11));
        self::assertSame(12, $previousDropoutsMap->get($round13and14));
        self::assertSame(2, $previousDropoutsMap->get($winnersChildRound));
        self::assertSame(11, $previousDropoutsMap->get($losersChildRound));
        self::assertSame(5, $previousDropoutsMap->get($rootRound));

        // (new StructureOutput()).output(structure, console);
    }
}
