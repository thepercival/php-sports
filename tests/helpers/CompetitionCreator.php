<?php

namespace Sports\TestHelper;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Association;
use Sports\Competition;
use Sports\Competition\Field;
use Sports\League;
use Sports\Competition\Referee;
use Sports\Season;
use Sports\Sport;
use Sports\Sport\Custom as SportCustom;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Competition\Sport\Service as CompetitionSportService;
use SportsHelpers\GameMode;

trait CompetitionCreator
{
    /**
     * @var Competition|null
     */
    protected $competition;
    /**
     * @var Sport|null
     */
    protected $sport;

    protected function createCompetition(): Competition
    {
        if ($this->competition !== null) {
            return $this->competition;
        }

        $league = new League(new Association("knvb"), "my league");
        $season = new Season("2018/2019", new Period(
            new DateTimeImmutable("2018-08-01"),
            new DateTimeImmutable("2019-07-01"),
        ));
        $this->competition = new Competition($league, $season);
        $this->competition->setId(0);
        $this->competition->setStartDateTime(new DateTimeImmutable("2030-01-01T12:00:00.000Z"));
        $referee1 = new Referee($this->competition);
        $referee1->setInitials("111");
        $referee2 = new Referee($this->competition);
        $referee2->setInitials("222");

        $competitionSportService = new CompetitionSportService();
        $competitionSport = $competitionSportService->createDefault($this->createSport(), $this->competition);
        $field1 = new Field($competitionSport);
        $field1->setName("1");
        $field2 = new Field($competitionSport);
        $field2->setName("2");

        return $this->competition;
    }

    protected function createSport(): Sport
    {
        if ($this->sport !== null) {
            return $this->sport;
        }

        $this->sport = new Sport("voetbal", true, 2, GameMode::AGAINST);
        $this->sport->setCustomId(SportCustom::Football);
        return $this->sport;
    }

    protected function getCompetitionSport(): ?CompetitionSport
    {
        if ($this->competition === null) {
            return null;
        }
        return $this->competition->getSports()->count() > 0 ? $this->competition->getSports()->first() : null;
    }
}
