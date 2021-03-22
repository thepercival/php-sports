<?php
declare(strict_types=1);

namespace Sports\Place\SportPerformance;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Score\Config\Service as ScoreConfigService;
use Sports\Place;
use Sports\Round;
use Sports\Place\SportPerformance;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Against as AgainstGame;

abstract class Calculator
{
    protected ScoreConfigService $scoreConfigService;

    public function __construct(protected Round $round, protected CompetitionSport $competitionSport)
    {
        $this->scoreConfigService = new ScoreConfigService();
    }

    /*protected static function getIndex(Place $place): string
    {
        return $place->getPoule()->getNumber() . '-' . $place->getNumber();
    }*/

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
            $map[$performance->getRoundLocationId()] = $performance;
        }
        return $map;
    }
}
