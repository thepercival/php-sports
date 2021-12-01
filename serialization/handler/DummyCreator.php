<?php
declare(strict_types=1);

namespace Sports\SerializationHandler;

use Sports\Association;
use Sports\Competition;
use Sports\Round\Number as RoundNumber;
use Sports\Competition\Sport as CompetitionSport;
use Sports\League;
use Sports\Season;
use Sports\Sport;
use SportsHelpers\GameMode;
use SportsHelpers\Sport\PersistVariant as PersistSportVariant;

class DummyCreator
{
    /**
     * @var Competition|null
     */
    private Competition|null $competition = null;
    /**
     * @var array<string|int, CompetitionSport>
     */
    private array $competitionSports  = [];

    public function createCompetition(): Competition
    {
        if ($this->competition === null) {
            $association = new Association("knvb");
            $league = new League($association, "my league");
            $season = new Season("123", new \League\Period\Period("2018-12-17T11:33:15.710Z", "2018-12-17T11:33:15.710Z"));
            $this->competition = new Competition($league, $season);
            $this->competition->setStartDateTime(new \DateTimeImmutable("2018-12-17T12:00:00.000Z"));
        }
        return $this->competition;
    }

    public function createCompetitionSport(Competition $competition, int $competitionSportId, int $sportId): CompetitionSport
    {
        if (array_key_exists($competitionSportId, $this->competitionSports)) {
            return $this->competitionSports[$competitionSportId];
        }
        $getCompetitionSport = function (int $competitionSportId) use ($competition): ?CompetitionSport {
            foreach ($competition->getSports() as $competitionSport) {
                if ($competitionSport->getId() == $competitionSportId) {
                    return $competitionSport;
                }
            }
            return null;
        };
        $competitionSport = $getCompetitionSport($competitionSportId);
        if ($competitionSport === null) {
            $defaultNrOfSidePlaces = 1;
            $sport = new Sport(
                'dummy',
                true,
                GameMode::Against,
                $defaultNrOfSidePlaces * 2
            );
            $sport->setId($sportId);
            $competitionSport = new CompetitionSport(
                $sport,
                $competition,
                new PersistSportVariant(
                    $sport->getDefaultGameMode(),
                    $defaultNrOfSidePlaces,
                    $defaultNrOfSidePlaces,
                    0,
                    $defaultNrOfSidePlaces <= 2 ? 1 : 0,
                    $defaultNrOfSidePlaces > 2 ? 1 : 0
                )
            );
            $competitionSport->setId($competitionSportId);
            $this->competitionSports[$competitionSportId] = $competitionSport;
        }
        return $competitionSport;
    }

    public function createRoundNumber(): RoundNumber
    {
        return new RoundNumber($this->createCompetition());
    }
}
