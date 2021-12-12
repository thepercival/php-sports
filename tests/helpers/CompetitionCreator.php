<?php

declare(strict_types=1);

namespace Sports\TestHelper;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Association;
use Sports\Competition;
use Sports\Competition\Field;
use Sports\Competition\Referee;
use Sports\Competition\Sport as CompetitionSport;
use Sports\League;
use Sports\Season;
use Sports\Sport;
use Sports\Sport\Custom as SportCustom;
use SportsHelpers\GameMode;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;

trait CompetitionCreator
{
    /**
     * @var Competition|null
     */
    protected $competition;
    /**
     * @var CompetitionSport|null
     */
    protected $competitionSport;

    protected function createCompetition(
        SingleSportVariant|AgainstSportVariant|AllInOneGameSportVariant|null $sportVariant = null
    ): Competition {
        if ($this->competition !== null) {
            return $this->competition;
        }

        $league = new League(new Association("knvb"), "my league");
        $season = new Season("2018/2019", new Period(
            new DateTimeImmutable("2018-08-01"),
            new DateTimeImmutable("2019-07-01"),
        ));
        $competition = new Competition($league, $season);
        $competition->setId(0);
        $competition->setStartDateTime(new DateTimeImmutable("2030-01-01T12:00:00.000Z"));
        new Referee($competition, '111');
        new Referee($competition, '222');

        $this->createCompetitionSport($competition, $sportVariant);
        $this->competition = $competition;
        return $competition;
    }

    /*protected function createSportVariant(): Sport
    {
        if ($this->sport !== null) {
            return $this->sport;
        }

        $this->sport = new Sport("voetbal", true, 2, GameMode::Against);
        $this->sport->setCustomId(SportCustom::Football);
        return $this->sport;
    }*/

    protected function createCompetitionSport(
        Competition $competition,
        SingleSportVariant|AgainstSportVariant|AllInOneGameSportVariant|null $sportVariant
    ): void {
        if ($this->competitionSport !== null) {
            return;
        }

        $sport = new Sport("voetbal", true, GameMode::Against, 1);
        $sport->setCustomId(SportCustom::Football);

        if ($sportVariant === null) {
            $sportVariant = new AgainstSportVariant(1, 1, 1, 0);
        }
        $this->competitionSport = new CompetitionSport($sport, $competition, $sportVariant->toPersistVariant());
        $field1 = new Field($this->competitionSport);
        $field1->setName("1");
        $field2 = new Field($this->competitionSport);
        $field2->setName("2");
    }
}
