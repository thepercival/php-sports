<?php

declare(strict_types=1);

namespace Sports\Tests\Team\Role;

use League\Period\Period;
use Sports\Association;
use Sports\Competition;
use Sports\Competition\CompetitionSport as CompetitionSport;
use Sports\Competition\Sport\Editor as CompetitionSportEditor;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Participation as GameParticipation;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\League;
use Sports\Person;
use Sports\Planning\PlanningConfigService as PlanningConfigService;
use Sports\Ranking\PointsCalculation;
use Sports\Season;
use Sports\Sport;
use Sports\Sport\FootballLine;
use Sports\Structure\StructureEditor;
use Sports\Team;
use Sports\Team\Player as TeamPlayer;
use Sports\Team\Role\Validator as Validator;
use Sports\TestHelper\CompetitionCreator;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Sports\AgainstOneVsOne;

final class ValidatorTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator;

    public function testPlayerPartiallyBeforeSeasonStart(): void
    {
        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);
        $seasonStart = $seasonPeriod->startDate->sub(new \DateInterval('P1D'));
        self::assertInstanceOf(\DateTimeImmutable::class, $seasonStart);
        $period = Period::fromDate($seasonStart, $seasonPeriod->startDate->add(new \DateInterval('P5D')));
        $this->testPlayerPartiallyHelper($season, $period);
    }

    private function testPlayerPartiallyHelper(Season $season, Period $playerPeriod): void
    {
        $validator = new Validator();

        $association = new Association('testAssociation');
        $league = new League($association, 'testLeague');

        $competition = new Competition($league, $season);
        $team = new Team($association, 'testTeam');
        $person = new Person('FirstName', null, 'LastName');

        $teamPlayer = new TeamPlayer($team, $person, $playerPeriod, FootballLine::Defense->value);

        self::expectException(\Exception::class);
        $validator->validate($teamPlayer, $competition);
    }

    public function testPlayerPartiallyAfterSeasonEnd(): void
    {
        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);
        $seasonStart = $seasonPeriod->endDate->sub(new \DateInterval('P5D'));
        self::assertInstanceOf(\DateTimeImmutable::class, $seasonStart);
        $period = Period::fromDate(
            $seasonStart,
            $seasonPeriod->endDate->add(new \DateInterval('P5D'))

        );
        $this->testPlayerPartiallyHelper($season, $period);
    }

    public function testPlayerAPartiallyBeforeB(): void
    {
        $validator = new Validator();

        $association = new Association('testAssociation');
        $league = new League($association, 'testLeague');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $competition = new Competition($league, $season);
        $team = new Team($association, 'testTeam');
        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;
        $periodA = Period::fromDate($seasonStart, $seasonStart->add(new \DateInterval('P8D')));
        $periodB = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P12D'))
        );

        new TeamPlayer($team, $person, $periodA, FootballLine::Defense->value);
        $teamPlayerB = new TeamPlayer($team, $person, $periodB, FootballLine::Defense->value);

        self::expectException(\Exception::class);
        $validator->validate($teamPlayerB, $competition);
    }

    public function testPlayerAEndDateEqualToPlayerBStartDate(): void
    {
        $validator = new Validator();

        $association = new Association('testAssociation');
        $league = new League($association, 'testLeague');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $competition = new Competition($league, $season);
        $team = new Team($association, 'testTeam');
        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;
        $periodA = Period::fromDate($seasonStart, $seasonStart->add(new \DateInterval('P8D')));
        $periodB = Period::fromDate(
            $seasonStart->add(new \DateInterval('P8D')), $seasonPeriod->endDate
        );

        new TeamPlayer($team, $person, $periodA, FootballLine::Defense->value);
        $teamPlayerB = new TeamPlayer($team, $person, $periodB, FootballLine::Defense->value);

        self::expectNotToPerformAssertions();
        $validator->validate($teamPlayerB, $competition);
    }

    public function testPlayerAPartiallyAfterB(): void
    {
        $validator = new Validator();

        $association = new Association('testAssociation');
        $league = new League($association, 'testLeague');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $competition = new Competition($league, $season);
        $team = new Team($association, 'testTeam');
        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;
        $periodA = Period::fromDate($seasonStart, $seasonStart->add(new \DateInterval('P8D')));
        $periodB = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P12D'))
        );

        new TeamPlayer($team, $person, $periodA, FootballLine::Defense->value);
        $teamPlayerB = new TeamPlayer($team, $person, $periodB, FootballLine::Defense->value);

        self::expectException(\Exception::class);
        $validator->validate($teamPlayerB, $competition);
    }

    public function testPlayerAWithinB(): void
    {
        $validator = new Validator();

        $association = new Association('testAssociation');
        $league = new League($association, 'testLeague');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $competition = new Competition($league, $season);
        $team = new Team($association, 'testTeam');
        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;
        $periodA = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P8D'))
        );
        $periodB = Period::fromDate($seasonStart, $seasonStart->add(new \DateInterval('P12D')));

        new TeamPlayer($team, $person, $periodA, FootballLine::Defense->value);
        $teamPlayerB = new TeamPlayer($team, $person, $periodB, FootballLine::Defense->value);

        self::expectException(\Exception::class);
        $validator->validate($teamPlayerB, $competition);
    }

    public function testPlayerBWithinA(): void
    {
        $validator = new Validator();

        $association = new Association('testAssociation');
        $league = new League($association, 'testLeague');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $competition = new Competition($league, $season);
        $team = new Team($association, 'testTeam');
        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;
        $periodA = Period::fromDate($seasonStart, $seasonStart->add(new \DateInterval('P12D')));
        $periodB = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P8D'))
        );

        new TeamPlayer($team, $person, $periodA, FootballLine::Defense->value);
        $teamPlayerB = new TeamPlayer($team, $person, $periodB, FootballLine::Defense->value);

        self::expectException(\Exception::class);
        $validator->validate($teamPlayerB, $competition);
    }

    public function testGameOutsidePlayerPeriod(): void
    {
        $validator = new Validator();

        $association = new Association('testAssociation');
        $league = new League($association, 'testLeague');

        $seasonPeriod = Period::fromDate('2015-07-01', '2016-07-01');
        $season = new Season('2015/2016', $seasonPeriod);

        $competition = new Competition($league, $season);
        $team = new Team($association, 'testTeam');
        $person = new Person('FirstName', null, 'LastName');

        $seasonStart = $seasonPeriod->startDate;
        $period = Period::fromDate(
            $seasonStart->add(new \DateInterval('P4D')),
            $seasonStart->add(new \DateInterval('P8D'))
        );

        $teamPlayer = new TeamPlayer($team, $person, $period, FootballLine::Defense->value);

        $dateTime = $period->startDate->sub(new \DateInterval('P2D'));
        self::assertInstanceOf(\DateTimeImmutable::class, $dateTime);
        $this->createGame(
            $competition,
            $teamPlayer,
            $dateTime
        );

        self::expectException(\Exception::class);
        $validator->validate($teamPlayer, $competition);
    }

    protected function createGame(Competition $competition, TeamPlayer $player, \DateTimeImmutable $startDateTime): void
    {
        $structureEditor = new StructureEditor(
            new CompetitionSportEditor(),
            new PlanningConfigService()
        );
        $structure = $structureEditor->create($competition, [1]);
        $poule = $structure->getSingleCategory()->getRootRound()->getFirstPoule();
        $sport = new Sport('voetbal',true,new AgainstOneVsOne());
        $competitionSport = new CompetitionSport($sport, $competition,
            PointsCalculation::AgainstGamePoints, 3, 1, 2, 1, 0
        );
        $game = new AgainstGame(
            $poule,
            1,
            $startDateTime,
            $competitionSport,
            1,
            1
        );
        $gamePlace = new AgainstGamePlace($game, $poule->getPlace(1), AgainstSide::Home);
        new GameParticipation($gamePlace, $player, 0);
    }
}
