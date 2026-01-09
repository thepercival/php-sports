<?php

declare(strict_types=1);

namespace Sports\Tests\Team\Role;

use League\Period\Period;
use Monolog\Logger;
use Sports\Association;
use Sports\Person;
use Sports\Season;
use Sports\Sport\FootballLine;
use Sports\Team;

final class EditorTest extends \PHPUnit\Framework\TestCase
{
    public function testGameDateTimeOutsideSeason(): void
    {
        $editor = new Team\Role\Editor(new Logger('tmp'));

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;

        $periodBefore = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P8D'))
        );
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->add(new \DateInterval('PT12H')); // border is 1 after start and 1 before end
        self::expectException(\Exception::class);
        $editor->update($season, $person, $gameDateTime, $team, FootballLine::Defense);
    }

    public function testCreateWithoutOther(): void
    {
        $editor = new Team\Role\Editor(new Logger('tmp'));

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;
//        $period = Period::fromDate($seasonStart->modify('+8 days'), $seasonStart->modify('+10 days'));

        $gameDateTime = $seasonStart->add(new \DateInterval('P8D'));
        $editor->update($season, $person, $gameDateTime, $team, FootballLine::Defense);
        self::assertCount(1, $person->getPlayers(null, $season->getPeriod()));
    }

    public function testOverlappingSameTeamSameLine(): void
    {
        $editor = new Team\Role\Editor(new Logger('tmp'));

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;

        $period = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P8D'))
        );
        new Team\Player($team, $person, $period, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->add(new \DateInterval('P6D'));
        $updatedPlayer = $editor->update($season, $person, $gameDateTime, $team, FootballLine::Defense);
        self::assertNull($updatedPlayer);
    }

    public function testOverlappingSameTeamOtherLine(): void
    {
        $editor = new Team\Role\Editor(new Logger('tmp'));

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;

        $periodBefore = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P8D'))
        );
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);
        $otherTeam = new Team($association, 'otherTeam');
        $gameDateTime = $seasonStart->add(new \DateInterval('P6D'));
        self::expectException(\Exception::class);
        $editor->update($season, $person, $gameDateTime, $otherTeam, FootballLine::Midfield);
    }

    public function testOverlappingOtherTeamSameLine(): void
    {
        $editor = new Team\Role\Editor(new Logger('tmp'));

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;

        $periodBefore = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P8D'))
        );
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $otherTeam = new Team($association, 'otherTeam');

        $gameDateTime = $seasonStart->add(new \DateInterval('P6D'));
        $newPlayer = $editor->update($season, $person, $gameDateTime, $otherTeam, FootballLine::Defense);
        self::assertNotNull($newPlayer);
    }

    public function testOneBeforeSameTeamSameLine(): void
    {
        $editor = new Team\Role\Editor(new Logger('tmp'));

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;

        $periodBefore = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P8D'))
        );
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->add(new \DateInterval('P20D'));
        $changedPeriod = $editor->update($season, $person, $gameDateTime, $team, FootballLine::Defense);
        self::assertCount(1, $person->getPlayers(null, $season->getPeriod()));
        self::assertNotNull($changedPeriod);
        self::assertEquals($gameDateTime->add(new \DateInterval('P1D')), $changedPeriod->getEndDateTime());
    }

    public function testOneBeforeSameTeamOtherLine(): void
    {
        $editor = new Team\Role\Editor(new Logger('tmp'));

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;

        $periodBefore = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P8D'))
        );
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->add(new \DateInterval('P20D'));
        self::expectException(\Exception::class);
        $editor->update($season, $person, $gameDateTime, $team, FootballLine::Midfield);
    }

    public function testOneBeforeOtherTeamSameLine(): void
    {
        $editor = new Team\Role\Editor(new Logger('tmp'));

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;
        $seasonEnd = $seasonPeriod->endDate;

        $periodBefore = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P8D'))
        );
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $otherTeam = new Team($association, 'otherTeam');

        $gameDateTime = $seasonStart->add(new \DateInterval('P20D'));
        $addedPlayer = $editor->update($season, $person, $gameDateTime, $otherTeam, FootballLine::Defense);
        self::assertCount(2, $person->getPlayers(null, $season->getPeriod()));
        self::assertNotNull($addedPlayer);
        self::assertEquals($gameDateTime->modify('-1 days'), $addedPlayer->getStartDateTime());
        self::assertEquals($seasonEnd->modify('-1 days'), $addedPlayer->getEndDateTime());
    }

    public function testTwoBefore(): void
    {
        $editor = new Team\Role\Editor(new Logger('tmp'));

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;

        $periodBefore = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P8D'))
        );
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $otherTeam = new Team($association, 'otherTeam');

        $periodBefore = Period::fromDate(
            $seasonStart->add(new \DateInterval('P12D')),
            $seasonStart->add(new \DateInterval('P16D'))
        );
        new Team\Player($otherTeam, $person, $periodBefore, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->add(new \DateInterval('P20D'));
        $updatedPlayer = $editor->update($season, $person, $gameDateTime, $otherTeam, FootballLine::Defense);
        self::assertCount(2, $person->getPlayers(null, $season->getPeriod()));
        self::assertNotNull($updatedPlayer);
        self::assertEquals($gameDateTime->add(new \DateInterval('P1D')), $updatedPlayer->getEndDateTime());
    }

    public function testOneAftereSameTeamSameLine(): void
    {
        $editor = new Team\Role\Editor(new Logger('tmp'));

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;

        $periodBefore = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P8D'))
        );
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->add(new \DateInterval('P2D'));
        $changedPeriod = $editor->update($season, $person, $gameDateTime, $team, FootballLine::Defense);
        self::assertCount(1, $person->getPlayers(null, $season->getPeriod()));
        self::assertNotNull($changedPeriod);
        self::assertEquals($gameDateTime->modify('-1 days'), $changedPeriod->getStartDateTime());
    }

    public function testOneAfterSameTeamOtherLine(): void
    {
        $editor = new Team\Role\Editor(new Logger('tmp'));

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;

        $periodBefore = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P8D'))
        );
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->add(new \DateInterval('P2D'));
        self::expectException(\Exception::class);
        $editor->update($season, $person, $gameDateTime, $team, FootballLine::Midfield);
    }

    public function testOneAfterOtherTeamSameLine(): void
    {
        $editor = new Team\Role\Editor(new Logger('tmp'));

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;
        $seasonEnd = $seasonPeriod->endDate;

        $periodBefore = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P8D'))
        );
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $otherTeam = new Team($association, 'otherTeam');

        $gameDateTime = $seasonStart->add(new \DateInterval('P2D'));
        $addedPlayer = $editor->update($season, $person, $gameDateTime, $otherTeam, FootballLine::Defense);
        self::assertCount(2, $person->getPlayers(null, $season->getPeriod()));
        self::assertNotNull($addedPlayer);
        self::assertEquals($gameDateTime->modify('-1 days'), $addedPlayer->getStartDateTime());
        self::assertEquals($seasonEnd->modify('-1 days'), $addedPlayer->getEndDateTime());
    }

    public function testTwoAfter(): void
    {
        $editor = new Team\Role\Editor(new Logger('tmp'));

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;

        $periodBefore = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P8D'))
        );
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $otherTeam = new Team($association, 'otherTeam');

        $periodBefore = Period::fromDate(
            $seasonStart->add(new \DateInterval('P12D')),
            $seasonStart->add(new \DateInterval('P16D'))
        );
        new Team\Player($otherTeam, $person, $periodBefore, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->add(new \DateInterval('P2D'));
        $updatedPlayer = $editor->update($season, $person, $gameDateTime, $team, FootballLine::Defense);
        self::assertCount(2, $person->getPlayers(null, $season->getPeriod()));
        self::assertNotNull($updatedPlayer);
        self::assertEquals($gameDateTime->modify('-1 days'), $updatedPlayer->getStartDateTime());
    }

    public function testOneWithinOther(): void
    {
        $editor = new Team\Role\Editor(new Logger('tmp'));

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;
        $seasonEnd = $seasonPeriod->endDate;

        $period = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P8D'))
        );
        new Team\Player($team, $person, $period, FootballLine::Defense->value);

        $otherTeam = new Team($association, 'otherTeam');

        $gameDateTime = $seasonStart->add(new \DateInterval('P6D'));
        $newPlayer = $editor->update($season, $person, $gameDateTime, $otherTeam, FootballLine::Defense);
        self::assertCount(2, $person->getPlayers(null, $season->getPeriod()));
        self::assertNotNull($newPlayer);
        self::assertEquals($gameDateTime->modify('-1 days'), $newPlayer->getStartDateTime());
        self::assertEquals($seasonEnd->modify('-1 days'), $newPlayer->getEndDateTime());
    }

    // psv 09-01    10-01
    // psv particfey - psv 08-13 samenvoegen als er niets tussen zit
//    public function testCreateWithoutOther(): void
//    {
//        $editor = new Team\Role\Editor();
//
//        $association = new Association('testAssociation');
//        $team = new Team($association, 'testTeam');
//
//        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
//        $season = new Season('2015/2016', $seasonPeriod);
//
//        $person = new Person('FirstName', null, 'LastName');
//
//        $seasonStart = $seasonPeriod->startDate;
////        $period = Period::fromDate($seasonStart->modify('+8 days'), $seasonStart->modify('+10 days'));
//
//        $gameDateTime = $seasonStart->modify('+8 days');
//        $editor->update($season, $person, $gameDateTime, $team, FootballLine::Defense);
//        self::assertCount(1, $person->getPlayers(null, $season->getPeriod()));
//    }

//    // psv 09-01    10-01
//    // psv particfey - psv 08-13 samenvoegen als er niets tussen zit
//    public function testGameBeforePlayerPeriodSameTeam(): void
//    {
//        $validator = new Validator();
//
//        $association = new Association('testAssociation');
//        $league = new League($association, 'testLeague');
//
//        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
//        $season = new Season('2015/2016', $seasonPeriod);
//
//        $competition = new Competition($league, $season);
//        $team = new Team($association, 'testTeam');
//        $person = new Person('FirstName', null, 'LastName');
//
//        $seasonStart = $seasonPeriod->startDate;
//        $period = Period::fromDate($seasonStart->modify('+8 days'), $seasonStart->modify('+10 days'));
//
//        $teamPlayer = new TeamPlayer($team, $person, $period, FootballLine::Defense->value);
//
//        $gameStartDateTime = $seasonStart->modify('+4 days');
//        self::expectException(\Exception::class);
//        $validator->validate($teamPlayerB, $competition);
//    }
}
