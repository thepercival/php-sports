<?php

namespace Sports\Tests\Priority;

use Sports\Competition\Referee;
use Sports\TestHelper\CompetitionCreator;
use Sports\Priority\Service as PriorityService;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator;

    public function testGap()
    {
        $competition = $this->createCompetition();

        $referee2 = $competition->getReferee(2);

        $referee4 = new Referee($competition, 4);

        $priorityService = new PriorityService($competition->getReferees()->toArray());
        $changed = $priorityService->upgrade($referee4);

        self::assertCount(2, $changed);
        self::assertSame($referee4, $changed[0]);
        self::assertSame($referee2, $changed[1]);
    }

    public function testAlreadyHighest()
    {
        $competition = $this->createCompetition();

        $referee1 = $competition->getReferee(1);

        $priorityService = new PriorityService($competition->getReferees()->toArray());
        $changed = $priorityService->upgrade($referee1);

        self::assertCount(0, $changed);
    }

    public function testNormal()
    {
        $competition = $this->createCompetition();

        $referee1 = $competition->getReferee(1);
        $referee2 = $competition->getReferee(2);

        $priorityService = new PriorityService($competition->getReferees()->toArray());
        $changed = $priorityService->upgrade($referee2);

        self::assertCount(2, $changed);
        self::assertSame($referee2, $changed[0]);
        self::assertSame($referee1, $changed[1]);
    }
}
