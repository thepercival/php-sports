<?php
declare(strict_types=1);

namespace Sports\Place\SportPerformance;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Together as TogetherGame;
use Sports\Score\Config\Service as ScoreConfigService;
use Sports\Place;
use Sports\Round;
use Sports\Place\SportPerformance;

abstract class Calculator
{
    protected ScoreConfigService $scoreConfigService;

    public function __construct(protected Round $round, protected CompetitionSport $competitionSport)
    {
        $this->scoreConfigService = new ScoreConfigService();
    }

    /**
     * @param list<Place> $places
     * @param list<TogetherGame|AgainstGame> $games
     * @return list<SportPerformance>
     */
    abstract public function getPerformances(array $places, array $games): array;

    /**
     * @param list<Place> $places
     * @return list<SportPerformance>
     */
    protected function createPerformances(array $places): array
    {
        return array_values(array_map(function (Place $place): SportPerformance {
            return new SportPerformance($this->competitionSport, $place, $place->getPenaltyPoints());
        }, $places));
    }

    /**
     * @param array<SportPerformance> $performances
     * @return array<SportPerformance>
     */
    protected function getPerformanceMap(array $performances): array
    {
        $map = [];
        foreach ($performances as $performance) {
            $map[$performance->getPlaceLocation()->getRoundLocationId()] = $performance;
        }
        return $map;
    }
}
