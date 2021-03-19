<?php
declare(strict_types=1);

namespace Sports\Tests;

use PHPUnit\Framework\TestCase;
use Sports\Structure\Service as StructureService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\TestHelper\CompetitionCreator;

class StructureTest extends TestCase
{
    use CompetitionCreator;

    public function testBasics(): void
    {
        $competition = $this->createCompetition();
        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 16, 4);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        self::assertSame($rootRound->getNumber(), $firstRoundNumber);

        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);

        self::assertSame($rootRound->getNumber()->getNext(), $structure->getLastRoundNumber());

        self::assertSame(count($structure->getRoundNumbers()), 2);

        self::assertSame($structure->getRoundNumber(1), $firstRoundNumber);
        self::assertSame($structure->getRoundNumber(2), $firstRoundNumber->getNext());
        self::assertSame($structure->getRoundNumber(3), null);
        self::assertSame($structure->getRoundNumber(0), null);
    }

    public function testSetStructureNumbers(): void
    {
        $competition = $this->createCompetition();
        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 16, 4);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        self::assertSame($rootRound->getNumber(), $firstRoundNumber);

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 2);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 2);

        $structure->setStructureNumbers();

        $childWinnersRound = $rootRound->getChild(QualifyGroup::WINNERS, 1);
        $childLosersRound = $rootRound->getChild(QualifyGroup::LOSERS, 1);

        self::assertNotNull($childWinnersRound);
        self::assertNotNull($childLosersRound);

        self::assertSame($childWinnersRound->getStructureNumber(), 0);
        self::assertSame($rootRound->getStructureNumber(), 2);
        self::assertSame($childLosersRound->getStructureNumber(), 14);

        self::assertSame($rootRound->getPoule(1)->getStructureNumber(), 1);
        self::assertSame($rootRound->getPoule(4)->getStructureNumber(), 4);
        self::assertSame($childWinnersRound->getPoule(1)->getStructureNumber(), 5);
        self::assertSame($childLosersRound->getPoule(1)->getStructureNumber(), 6);
    }
}
