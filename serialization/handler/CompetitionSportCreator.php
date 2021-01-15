<?php
declare(strict_types=1);

namespace Sports\SerializationHandler;

use Sports\Competition;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Sport;
use SportsHelpers\SportConfig;

class CompetitionSportCreator
{
    /**
     * @var array | CompetitionSport[]
     */
    private static $competitionSports  = [];

    public function create(Competition $competition, int $competitionSportId, int $sportId): CompetitionSport
    {
        if (array_key_exists($competitionSportId, self::$competitionSports)) {
            return self::$competitionSports[$competitionSportId];
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
            $sport = new Sport(
                'dummy',
                true,
                2,
                SportConfig::GAMEMODE_AGAINST
            );
            $sport->setId($sportId);
            $competitionSport = new CompetitionSport($sport, $competition);
            $competitionSport->setId($competitionSportId);
            self::$competitionSports[$competitionSportId] = $competitionSport;
        }
        return $competitionSport;
    }
}
