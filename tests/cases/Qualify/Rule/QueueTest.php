<?php
declare(strict_types=1);

namespace Sports\Tests\Qualify\Rule;

use Sports\Qualify\Rule\Queue as QualifyRuleQueue;
use PHPUnit\Framework\TestCase;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\SetScores;
use Sports\Structure\Service as StructureService;
use Sports\Qualify\Group as QualifyGroup;
use SportsHelpers\PouleStructure;

final class QueueTest extends TestCase
{
    use CompetitionCreator, SetScores;

    public function testAdd(): void
    {
        $competition = $this->createCompetition();
        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, new PouleStructure([5]));
        $rootRound = $structure->getRootRound();
        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 2);

        $winnersRound = $rootRound->getChild(QualifyGroup::WINNERS, 1);
        self::assertNotNull($winnersRound);
        $firstPlace = $winnersRound->getPoule(1)->getPlace(1);
        $fromQualifyRule = $firstPlace->getFromQualifyRule();
        self::assertNotNull($fromQualifyRule);
        $queue = new QualifyRuleQueue();
        $queue->add(QualifyRuleQueue::START, $fromQualifyRule);

        self::assertFalse($queue->isEmpty());
    }

    public function testRemove(): void
    {
        $competition = $this->createCompetition();
        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, new PouleStructure([5]));
        $rootRound = $structure->getRootRound();
        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 2);

        $winnersRound = $rootRound->getChild(QualifyGroup::WINNERS, 1);
        self::assertNotNull($winnersRound);
        $firstPlace = $winnersRound->getPoule(1)->getPlace(1);
        $fromQualifyRule = $firstPlace->getFromQualifyRule();
        $queue = new QualifyRuleQueue();
        self::assertNotNull($fromQualifyRule);
        $queue->add(QualifyRuleQueue::START, $fromQualifyRule);
        $queue->remove(QualifyRuleQueue::END);
        self::assertTrue($queue->isEmpty());
    }

    public function testMoveCenterSingleRuleBack(): void
    {
        $competition = $this->createCompetition();
        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, new PouleStructure([3,3,3]));
        $rootRound = $structure->getRootRound();
        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 3);

        $winnersRound = $rootRound->getChild(QualifyGroup::WINNERS, 1);
        self::assertNotNull($winnersRound);
        $fromQualifyRuleOne = $winnersRound->getPoule(1)->getPlace(1)->getFromQualifyRule();
        self::assertNotNull($fromQualifyRuleOne);
        $fromQualifyRuleTwo = $winnersRound->getPoule(1)->getPlace(2)->getFromQualifyRule();
        self::assertNotNull($fromQualifyRuleTwo);
        $fromQualifyRuleThree = $winnersRound->getPoule(1)->getPlace(3)->getFromQualifyRule();
        self::assertNotNull($fromQualifyRuleThree);
        $queue = new QualifyRuleQueue();
        $queue->add(QualifyRuleQueue::START, $fromQualifyRuleOne);
        $queue->add(QualifyRuleQueue::START, $fromQualifyRuleTwo);
        $queue->add(QualifyRuleQueue::START, $fromQualifyRuleThree);
        $queue->moveCenterSingleRuleBack(3);
        self::assertSame($queue->remove(QualifyRuleQueue::END), $fromQualifyRuleTwo);
    }
}

