<?php

declare(strict_types=1);

namespace Sports\Tests\Team\Role;

use League\Period\Period;
use PHPUnit\Framework\TestCase;
use Sports\Person;
use Sports\Sport\Custom as SportCustom;
use Sports\Team;
use Sports\Team\Player;
use Sports\Team\Role\Combiner as RoleCombiner;
use Sports\TestHelper\CompetitionCreator;

final class CombinerTest extends TestCase
{
    use CompetitionCreator;

    /**
     *  MODE_MULTIPLE_TEAMS_OF_A_TYPE_AT_THE_SAME_TIME
     *
     *  { Team: Y, Line: Defender, Period: A }
     *  { Team: Z, Line: Defender, Period: X }
     *
     *     |   A   |
     *         |       X       |
     *
     *  -----  MIGRATE --------
     *
     *     |   A   |
     *         |       X       |
     */
    public function testCombiningWithoutPlayersUpdate(): void
    {
        $competition = $this->createCompetition();
        $person = new Person('Al', null, 'Person');
        $combiner = new RoleCombiner($person, RoleCombiner::MODE_MULTIPLE_TEAMS_OF_A_TYPE_AT_THE_SAME_TIME);
        $teamY = new Team($competition->getLeague()->getAssociation(), 'team Y');
        $teamZ = new Team($competition->getLeague()->getAssociation(), 'team Z');
        $now = new \DateTimeImmutable();
        $fourMinutesBefore = $now->modify('-4 minutes');
        $twoMinutesBefore = $now->modify('-2 minutes');
        $twoMinutesAfter = $now->modify('2 minutes');
        $periodA = new Period($fourMinutesBefore, $now);
        $periodX = new Period($twoMinutesBefore, $twoMinutesAfter);

        $playerA = new Player($teamY, $person, $periodA, SportCustom::Football_Line_Defense);
        $combiner->combineWithPast($teamZ, $periodX, SportCustom::Football_Line_Defense);

        self::assertCount(2, $person->getPlayers()->toArray());

        self::assertSame($fourMinutesBefore, $playerA->getPeriod()->getStartDate());
        self::assertSame($now, $playerA->getPeriod()->getEndDate());
    }

    /**
     *  MODE_ONE_TEAM_OF_A_TYPE_AT_THE_SAME_TIME
     *
     *  { Team: Y, Line: Defender, Period: A }
     *  { Team: Z, Line: Defender, Period: X }
     *
     *     |   A   |
     *         |       X       |
     *
     *  -----  MIGRATE --------
     *
     *     |   A   |
     *         |       X       |
     */
    public function testNotMergableByTeam(): void
    {
        $competition = $this->createCompetition();
        $person = new Person('Al', null, 'Person');
        $combiner = new RoleCombiner($person);
        $teamY = new Team($competition->getLeague()->getAssociation(), 'team Y');
        $teamZ = new Team($competition->getLeague()->getAssociation(), 'team Z');
        $now = new \DateTimeImmutable();
        $fourMinutesBefore = $now->modify('-4 minutes');
        $twoMinutesBefore = $now->modify('-2 minutes');
        $twoMinutesAfter = $now->modify('2 minutes');
        $periodA = new Period($fourMinutesBefore, $now);
        $periodX = new Period($twoMinutesBefore, $twoMinutesAfter);

        new Player($teamY, $person, $periodA, SportCustom::Football_Line_Defense);
        $combiner->combineWithPast($teamZ, $periodX, SportCustom::Football_Line_Defense);

        self::assertCount(2, $person->getPlayers()->toArray());
    }

    /**
     *  MODE_ONE_TEAM_OF_A_TYPE_AT_THE_SAME_TIME
     *
     *  { Team: Y, Line: Defense, Period: A }
     *  { Team: Y, Line: Midfield, Period: X }
     *
     *     |   A   |
     *         |       X       |
     *
     *  -----  MIGRATE --------
     *
     *     |   A   |
     *         |       X       |
     */
    public function testNotMergableByLine(): void
    {
        $competition = $this->createCompetition();
        $person = new Person('Al', null, 'Person');
        $combiner = new RoleCombiner($person);
        $teamY = new Team($competition->getLeague()->getAssociation(), 'team Y');
        $now = new \DateTimeImmutable();
        $fourMinutesBefore = $now->modify('-4 minutes');
        $twoMinutesBefore = $now->modify('-2 minutes');
        $twoMinutesAfter = $now->modify('2 minutes');
        $periodA = new Period($fourMinutesBefore, $now);
        $periodX = new Period($twoMinutesBefore, $twoMinutesAfter);

        new Player($teamY, $person, $periodA, SportCustom::Football_Line_Defense);
        $combiner->combineWithPast($teamY, $periodX, SportCustom::Football_Line_Midfield);

        self::assertCount(2, $person->getPlayers()->toArray());
    }

    /**
     *  MODE_ONE_TEAM_OF_A_TYPE_AT_THE_SAME_TIME
     *
     *  { Team: Y, Line: Defender, Period: A }
     *  { Team: Y, Line: Defender, Period: X }
     *
     *     |   A   |
     *     |   X   |
     *
     *  -----  MIGRATE --------
     *
     *     |   A   |
     *     |   X   |
     */
    public function testNotMergableByPeriod(): void
    {
        $competition = $this->createCompetition();
        $person = new Person('Al', null, 'Person');
        $combiner = new RoleCombiner($person);
        $teamY = new Team($competition->getLeague()->getAssociation(), 'team Y');
        $now = new \DateTimeImmutable();
        $fourMinutesBefore = $now->modify('-4 minutes');
        $periodA = new Period($fourMinutesBefore, $now);
        $periodX = new Period($fourMinutesBefore, $now);

        $playerA = new Player($teamY, $person, $periodA, SportCustom::Football_Line_Defense);
        $combiner->combineWithPast($teamY, $periodX, SportCustom::Football_Line_Defense);

        self::assertCount(1, $person->getPlayers()->toArray());
        self::assertSame($fourMinutesBefore, $playerA->getPeriod()->getStartDate());
        self::assertSame($now, $playerA->getPeriod()->getEndDate());
    }

    /**
     *  MODE_ONE_TEAM_OF_A_TYPE_AT_THE_SAME_TIME
     *
     *  { Team: Y, Line: Defender, Period: A }
     *  { Team: Y, Line: Defender, Period: X }
     *
     *     |   A   |
     *                          |   X   |
     *
     *  -----  MIGRATE --------
     *
     *     |   A   |
     *                          |   X   |
     */
    public function testNotMergableByPeriodMaxDiff(): void
    {
        $competition = $this->createCompetition();
        $person = new Person('Al', null, 'Person');
        $combiner = new RoleCombiner($person);
        $teamY = new Team($competition->getLeague()->getAssociation(), 'team Y');
        $now = new \DateTimeImmutable();
        $fourMinutesBefore = $now->modify('-4 minutes');
        $tooMuchInPastStartDate = $now->modify('-9 months');
        $tooMuchInPastEndDate = $now->modify('-8 months');
        $periodA = new Period($tooMuchInPastStartDate, $tooMuchInPastEndDate);
        $periodX = new Period($fourMinutesBefore, $now);

        $playerA = new Player($teamY, $person, $periodA, SportCustom::Football_Line_Defense);
        $combiner->combineWithPast($teamY, $periodX, SportCustom::Football_Line_Defense);

        self::assertCount(2, $person->getPlayers()->toArray());
        self::assertSame($tooMuchInPastStartDate, $playerA->getPeriod()->getStartDate());
        self::assertSame($tooMuchInPastEndDate, $playerA->getPeriod()->getEndDate());
    }

    /**
     *  MODE_ONE_TEAM_OF_A_TYPE_AT_THE_SAME_TIME
     *
     *  { Team: Y, Line: Defender, Period: A }
     *  { Team: Y, Line: Defender, Period: X }
     *
     *                  |   A   |
     *     |   X   |
     *
     *  -----  MIGRATE --------
     *
     *                  |   A   |
     *     |   X   |
     */
    public function testNotMergableByPeriodFuture(): void
    {
        $competition = $this->createCompetition();
        $person = new Person('Al', null, 'Person');
        $combiner = new RoleCombiner($person);
        $teamY = new Team($competition->getLeague()->getAssociation(), 'team Y');
        $now = new \DateTimeImmutable();
        $fourMinutesBefore = $now->modify('-4 minutes');
        $twoMinutesBefore = $now->modify('-2 minutes');
        $twoMinutesAfter = $now->modify('2 minutes');

        $periodA = new Period($now, $twoMinutesAfter);
        $periodX = new Period($fourMinutesBefore, $twoMinutesBefore);

        $playerA = new Player($teamY, $person, $periodA, SportCustom::Football_Line_Defense);
        $combiner->combineWithPast($teamY, $periodX, SportCustom::Football_Line_Defense);

        self::assertCount(1, $person->getPlayers()->toArray());
        self::assertSame($now, $playerA->getPeriod()->getStartDate());
        self::assertSame($twoMinutesAfter, $playerA->getPeriod()->getEndDate());
    }

    /**
     *  MODE_MULTIPLE_TEAMS_OF_A_TYPE_AT_THE_SAME_TIME
     *
     *  { Team: Z, Line: Defender, Period: A }
     *  { Team: Y, Line: Defender, Period: X }
     *
     *                  |   A   |
     *     |   X   |
     *
     *  -----  MIGRATE --------
     *
     *                  |   A   |
     *     |   X   |
     */
    public function testNotMergableByPeriodFutureMultipleTeams(): void
    {
        $competition = $this->createCompetition();
        $person = new Person('Al', null, 'Person');
        $combiner = new RoleCombiner($person, RoleCombiner::MODE_MULTIPLE_TEAMS_OF_A_TYPE_AT_THE_SAME_TIME);
        $teamY = new Team($competition->getLeague()->getAssociation(), 'team Y');
        $teamZ = new Team($competition->getLeague()->getAssociation(), 'team Z');
        $now = new \DateTimeImmutable();
        $fourMinutesBefore = $now->modify('-4 minutes');
        $twoMinutesBefore = $now->modify('-2 minutes');
        $twoMinutesAfter = $now->modify('2 minutes');

        $periodA = new Period($now, $twoMinutesAfter);
        $periodX = new Period($fourMinutesBefore, $twoMinutesBefore);

        $playerA = new Player($teamZ, $person, $periodA, SportCustom::Football_Line_Defense);
        $combiner->combineWithPast($teamY, $periodX, SportCustom::Football_Line_Defense);

        self::assertCount(2, $person->getPlayers()->toArray());
        self::assertSame($now, $playerA->getPeriod()->getStartDate());
        self::assertSame($twoMinutesAfter, $playerA->getPeriod()->getEndDate());
    }

    /**
     *  MODE_ONE_TEAM_OF_A_TYPE_AT_THE_SAME_TIME
     *
     *  { Team: Y, Line: Defender, Period: A }
     *  { Team: Y, Line: Defender, Period: X }
     *
     *     |   A   |
     *         |       X       |
     *
     *  -----  MIGRATE --------
     *
     *     |          A         |
     */
    public function testMergable(): void
    {
        $competition = $this->createCompetition();
        $person = new Person('Al', null, 'Person');
        $combiner = new RoleCombiner($person);
        $teamY = new Team($competition->getLeague()->getAssociation(), 'team Y');
        $now = new \DateTimeImmutable();
        $fourMinutesBefore = $now->modify('-4 minutes');
        $twoMinutesBefore = $now->modify('-2 minutes');
        $twoMinutesAfter = $now->modify('2 minutes');

        $periodA = new Period($fourMinutesBefore, $now);
        $periodX = new Period($twoMinutesBefore, $twoMinutesAfter);

        $playerA = new Player($teamY, $person, $periodA, SportCustom::Football_Line_Defense);
        $combiner->combineWithPast($teamY, $periodX, SportCustom::Football_Line_Defense);

        self::assertCount(1, $person->getPlayers()->toArray());
        self::assertSame($fourMinutesBefore, $playerA->getPeriod()->getStartDate());
        self::assertSame($twoMinutesAfter, $playerA->getPeriod()->getEndDate());
    }


    /**
     *  MODE_ONE_TEAM_OF_A_TYPE_AT_THE_SAME_TIME
     *
     *  { Team: Y, Line: Defender, Period: A }
     *  { Team: Y, Line: Defender, Period: X }
     *
     *         |   A   |
     *     |   X   |
     *
     *  -----  MIGRATE --------
     *
     *         |   A   |
     *     |   X   |
     */
    public function testNotUpdatableByFuturePeriod(): void
    {
        $competition = $this->createCompetition();
        $person = new Person('Al', null, 'Person');
        $combiner = new RoleCombiner($person);
        $teamY = new Team($competition->getLeague()->getAssociation(), 'team Y');
        $now = new \DateTimeImmutable();
        $fourMinutesBefore = $now->modify('-4 minutes');
        $twoMinutesBefore = $now->modify('-2 minutes');
        $twoMinutesAfter = $now->modify('2 minutes');

        $periodA = new Period($twoMinutesBefore, $twoMinutesAfter);
        $periodX = new Period($fourMinutesBefore, $now);

        $playerA = new Player($teamY, $person, $periodA, SportCustom::Football_Line_Defense);
        $combiner->combineWithPast($teamY, $periodX, SportCustom::Football_Line_Defense);

        self::assertCount(1, $person->getPlayers()->toArray());
        self::assertSame($twoMinutesBefore, $playerA->getPeriod()->getStartDate());
        self::assertSame($twoMinutesAfter, $playerA->getPeriod()->getEndDate());
    }
}
