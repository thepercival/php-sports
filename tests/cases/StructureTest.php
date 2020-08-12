<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 12-6-2019
 * Time: 09:49
 */
namespace Sports\Tests;

use Sports\Structure\Service as StructureService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;

class StructureTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator;

    public function testBasics()
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

    public function testSetStructureNumbers()
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

        self::assertSame($rootRound->getChild(QualifyGroup::WINNERS, 1)->getStructureNumber(), 0);
        self::assertSame($rootRound->getStructureNumber(), 2);
        self::assertSame($rootRound->getChild(QualifyGroup::LOSERS, 1)->getStructureNumber(), 14);

        self::assertSame($rootRound->getPoule(1)->getStructureNumber(), 1);
        self::assertSame($rootRound->getPoule(4)->getStructureNumber(), 4);
        self::assertSame($rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1)->getStructureNumber(), 5);
        self::assertSame($rootRound->getChild(QualifyGroup::LOSERS, 1)->getPoule(1)->getStructureNumber(), 6);
    }
}
