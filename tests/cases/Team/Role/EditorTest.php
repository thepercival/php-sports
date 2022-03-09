<?php

declare(strict_types=1);

namespace Sports\Tests\Team\Role;

use League\Period\Period;
use Sports\Association;
use Sports\Person;
use Sports\Season;
use Sports\Sport\FootballLine;
use Sports\Team;

class EditorTest extends \PHPUnit\Framework\TestCase
{
    public function testGameDateTimeOutsideSeason(): void
    {
        $editor = new Team\Role\Editor();

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = new Period('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->getStartDate();

        $periodBefore = new Period($seasonStart->modify('+4 days'), $seasonStart->modify('+8 days'));
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->modify('+12 hours'); // border is 1 after start and one before end
        self::expectException(\Exception::class);
        $editor->update($season, $person, $gameDateTime, $team, FootballLine::Defense);
    }

    public function testCreateWithoutOther(): void
    {
        $editor = new Team\Role\Editor();

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = new Period('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->getStartDate();
//        $period = new Period($seasonStart->modify('+8 days'), $seasonStart->modify('+10 days'));

        $gameDateTime = $seasonStart->modify('+8 days');
        $editor->update($season, $person, $gameDateTime, $team, FootballLine::Defense);
        self::assertCount(1, $person->getPlayers(null, $season->getPeriod()));
    }

    public function testOverlappingSameTeamSameLine(): void
    {
        $editor = new Team\Role\Editor();

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = new Period('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->getStartDate();

        $period = new Period($seasonStart->modify('+4 days'), $seasonStart->modify('+8 days'));
        new Team\Player($team, $person, $period, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->modify('+6 days');
        $updatedPlayer = $editor->update($season, $person, $gameDateTime, $team, FootballLine::Defense);
        self::assertNull($updatedPlayer);
    }

    public function testOverlappingSameTeamOtherLine(): void
    {
        $editor = new Team\Role\Editor();

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = new Period('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->getStartDate();

        $periodBefore = new Period($seasonStart->modify('+4 days'), $seasonStart->modify('+8 days'));
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->modify('+6 days');
        self::expectException(\Exception::class);
        $editor->update($season, $person, $gameDateTime, $team, FootballLine::Midfield);
    }

    public function testOverlappingOtherTeamSameLine(): void
    {
        $editor = new Team\Role\Editor();

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = new Period('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->getStartDate();

        $periodBefore = new Period($seasonStart->modify('+4 days'), $seasonStart->modify('+8 days'));
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $otherTeam = new Team($association, 'otherTeam');

        $gameDateTime = $seasonStart->modify('+6 days');
        $newPlayer = $editor->update($season, $person, $gameDateTime, $otherTeam, FootballLine::Defense);
        self::assertNotNull($newPlayer);
    }

    public function testOneBeforeSameTeamSameLine(): void
    {
        $editor = new Team\Role\Editor();

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = new Period('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->getStartDate();

        $periodBefore = new Period($seasonStart->modify('+4 days'), $seasonStart->modify('+8 days'));
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->modify('+20 days');
        $changedPeriod = $editor->update($season, $person, $gameDateTime, $team, FootballLine::Defense);
        self::assertCount(1, $person->getPlayers(null, $season->getPeriod()));
        self::assertNotNull($changedPeriod);
        self::assertEquals($gameDateTime->modify('+1 days'), $changedPeriod->getEndDateTime());
    }

    public function testOneBeforeSameTeamOtherLine(): void
    {
        $editor = new Team\Role\Editor();

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = new Period('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->getStartDate();

        $periodBefore = new Period($seasonStart->modify('+4 days'), $seasonStart->modify('+8 days'));
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->modify('+20 days');
        self::expectException(\Exception::class);
        $editor->update($season, $person, $gameDateTime, $team, FootballLine::Midfield);
    }

    public function testOneBeforeOtherTeamSameLine(): void
    {
        $editor = new Team\Role\Editor();

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = new Period('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->getStartDate();
        $seasonEnd = $seasonPeriod->getEndDate();

        $periodBefore = new Period($seasonStart->modify('+4 days'), $seasonStart->modify('+8 days'));
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $otherTeam = new Team($association, 'otherTeam');

        $gameDateTime = $seasonStart->modify('+20 days');
        $addedPlayer = $editor->update($season, $person, $gameDateTime, $otherTeam, FootballLine::Defense);
        self::assertCount(2, $person->getPlayers(null, $season->getPeriod()));
        self::assertNotNull($addedPlayer);
        self::assertEquals($gameDateTime->modify('-1 days'), $addedPlayer->getStartDateTime());
        self::assertEquals($seasonEnd->modify('-1 days'), $addedPlayer->getEndDateTime());
    }

    public function testTwoBefore(): void
    {
        $editor = new Team\Role\Editor();

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = new Period('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->getStartDate();

        $periodBefore = new Period($seasonStart->modify('+4 days'), $seasonStart->modify('+8 days'));
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $otherTeam = new Team($association, 'otherTeam');

        $periodBefore = new Period($seasonStart->modify('+12 days'), $seasonStart->modify('+16 days'));
        new Team\Player($otherTeam, $person, $periodBefore, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->modify('+20 days');
        $updatedPlayer = $editor->update($season, $person, $gameDateTime, $otherTeam, FootballLine::Defense);
        self::assertCount(2, $person->getPlayers(null, $season->getPeriod()));
        self::assertNotNull($updatedPlayer);
        self::assertEquals($gameDateTime->modify('+1 days'), $updatedPlayer->getEndDateTime());
    }

    public function testOneAftereSameTeamSameLine(): void
    {
        $editor = new Team\Role\Editor();

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = new Period('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->getStartDate();

        $periodBefore = new Period($seasonStart->modify('+4 days'), $seasonStart->modify('+8 days'));
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->modify('+2 days');
        $changedPeriod = $editor->update($season, $person, $gameDateTime, $team, FootballLine::Defense);
        self::assertCount(1, $person->getPlayers(null, $season->getPeriod()));
        self::assertNotNull($changedPeriod);
        self::assertEquals($gameDateTime->modify('-1 days'), $changedPeriod->getStartDateTime());
    }

    public function testOneAfterSameTeamOtherLine(): void
    {
        $editor = new Team\Role\Editor();

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = new Period('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->getStartDate();

        $periodBefore = new Period($seasonStart->modify('+4 days'), $seasonStart->modify('+8 days'));
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->modify('+2 days');
        self::expectException(\Exception::class);
        $editor->update($season, $person, $gameDateTime, $team, FootballLine::Midfield);
    }

    public function testOneAfterOtherTeamSameLine(): void
    {
        $editor = new Team\Role\Editor();

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = new Period('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->getStartDate();
        $seasonEnd = $seasonPeriod->getEndDate();

        $periodBefore = new Period($seasonStart->modify('+4 days'), $seasonStart->modify('+8 days'));
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $otherTeam = new Team($association, 'otherTeam');

        $gameDateTime = $seasonStart->modify('+2 days');
        $addedPlayer = $editor->update($season, $person, $gameDateTime, $otherTeam, FootballLine::Defense);
        self::assertCount(2, $person->getPlayers(null, $season->getPeriod()));
        self::assertNotNull($addedPlayer);
        self::assertEquals($gameDateTime->modify('-1 days'), $addedPlayer->getStartDateTime());
        self::assertEquals($seasonEnd->modify('-1 days'), $addedPlayer->getEndDateTime());
    }

    public function testTwoAfter(): void
    {
        $editor = new Team\Role\Editor();

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = new Period('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->getStartDate();

        $periodBefore = new Period($seasonStart->modify('+4 days'), $seasonStart->modify('+8 days'));
        new Team\Player($team, $person, $periodBefore, FootballLine::Defense->value);

        $otherTeam = new Team($association, 'otherTeam');

        $periodBefore = new Period($seasonStart->modify('+12 days'), $seasonStart->modify('+16 days'));
        new Team\Player($otherTeam, $person, $periodBefore, FootballLine::Defense->value);

        $gameDateTime = $seasonStart->modify('+2 days');
        $updatedPlayer = $editor->update($season, $person, $gameDateTime, $team, FootballLine::Defense);
        self::assertCount(2, $person->getPlayers(null, $season->getPeriod()));
        self::assertNotNull($updatedPlayer);
        self::assertEquals($gameDateTime->modify('-1 days'), $updatedPlayer->getStartDateTime());
    }

    public function testOneWithinOther(): void
    {
        $editor = new Team\Role\Editor();

        $association = new Association('testAssociation');
        $team = new Team($association, 'testTeam');

        $seasonPeriod = new Period('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->getStartDate();
        $seasonEnd = $seasonPeriod->getEndDate();

        $period = new Period($seasonStart->modify('+4 days'), $seasonStart->modify('+8 days'));
        new Team\Player($team, $person, $period, FootballLine::Defense->value);

        $otherTeam = new Team($association, 'otherTeam');

        $gameDateTime = $seasonStart->modify('+6 days');
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
//        $seasonPeriod = new Period('2015-07-01', '2016-07-01');
//        $season = new Season('2015/2016', $seasonPeriod);
//
//        $person = new Person('FirstName', null, 'LastName');
//
//        $seasonStart = $seasonPeriod->getStartDate();
////        $period = new Period($seasonStart->modify('+8 days'), $seasonStart->modify('+10 days'));
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
//        $seasonPeriod = new Period('2015-07-01', '2016-07-01');
//        $season = new Season('2015/2016', $seasonPeriod);
//
//        $competition = new Competition($league, $season);
//        $team = new Team($association, 'testTeam');
//        $person = new Person('FirstName', null, 'LastName');
//
//        $seasonStart = $seasonPeriod->getStartDate();
//        $period = new Period($seasonStart->modify('+8 days'), $seasonStart->modify('+10 days'));
//
//        $teamPlayer = new TeamPlayer($team, $person, $period, FootballLine::Defense->value);
//
//        $gameStartDateTime = $seasonStart->modify('+4 days');
//        self::expectException(\Exception::class);
//        $validator->validate($teamPlayerB, $competition);
//    }
}
