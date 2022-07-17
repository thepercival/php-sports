<?php

declare(strict_types=1);

namespace Sports\Tests\Qualify\RoundRank;

use PHPUnit\Framework\TestCase;
use Sports\Qualify\RoundRank\Service as RoundRankService;
use Sports\TestHelper\CompetitionCreator;
use Sports\Qualify\Target as QualifyTarget;
use Sports\TestHelper\StructureEditorCreator;

final class ServiceTest extends TestCase
{
    use CompetitionCreator;
    use StructureEditorCreator;

    // [7,7] => W[5],L[5] => W[2],L[2],W[2],L[2]
    public function testSimple(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [7, 7]);
        $rootRound = $structure->getSingleCategory()->getRootRound();
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

        $roundRankService = new RoundRankService();
        self::assertSame(0, $roundRankService->getRank($round1and2));
        self::assertSame(9, $roundRankService->getRank($round10and11));
        self::assertSame(12, $roundRankService->getRank($round13and14));
        self::assertSame(2, $roundRankService->getRank($winnersChildRound));
        self::assertSame(11, $roundRankService->getRank($losersChildRound));
        self::assertSame(5, $roundRankService->getRank($rootRound));
        // (new StructureOutput()).output(structure, console);
    }
}
