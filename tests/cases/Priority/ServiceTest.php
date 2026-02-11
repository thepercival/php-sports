<?php

declare(strict_types=1);

namespace Sports\Tests\Priority;

use PHPUnit\Framework\TestCase;
use Sports\Competition\CompetitionReferee;
use Sports\TestHelper\CompetitionCreator;
use Sports\Priority\Service as PriorityService;

final class ServiceTest extends TestCase
{
    use CompetitionCreator;

    public function testGap(): void
    {
        $competition = $this->createCompetition();

        $referee2 = $competition->getReferee(2);

        $referee4 = new CompetitionReferee($competition, 'RF4', 4);

        $priorityService = new PriorityService(array_values($competition->getReferees()->toArray()));
        $changed = $priorityService->upgrade($referee4);

        self::assertCount(2, $changed);
        self::assertSame($referee4, $changed[0]);
        self::assertSame($referee2, $changed[1]);
    }

    public function testAlreadyHighest(): void
    {
        $competition = $this->createCompetition();

        $referee1 = $competition->getReferee(1);

        $priorityService = new PriorityService(array_values($competition->getReferees()->toArray()));
        $changed = $priorityService->upgrade($referee1);

        self::assertCount(0, $changed);
    }

    public function testNormal(): void
    {
        $competition = $this->createCompetition();

        $referee1 = $competition->getReferee(1);
        $referee2 = $competition->getReferee(2);

        $priorityService = new PriorityService(array_values($competition->getReferees()->toArray()));
        $changed = $priorityService->upgrade($referee2);

        self::assertCount(2, $changed);
        self::assertSame($referee2, $changed[0]);
        self::assertSame($referee1, $changed[1]);
    }
}
