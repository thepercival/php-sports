<?php
declare(strict_types=1);

namespace Sports\Place\SportPerformance\Calculator;

use Sports\Place\SportPerformance\Calculator;
use Sports\Place;
use Sports\Round;
use Sports\Place\SportPerformance;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Against as AgainstGame;
use Sports\Competition\Sport as CompetitionSport;

class Together extends Calculator
{
    public function __construct(Round $round, CompetitionSport $competitionSport)
    {
        parent::__construct($round, $competitionSport);
    }

    /**
     * @param array<Place> $places
     * @param array<TogetherGame|AgainstGame> $games
     * @return array<SportPerformance>
     */
    public function getPerformances(array $places, array $games): array
    {
        $performances = $this->createPerformances($places);
        $performanceMap = $this->getPerformanceMap($performances);
        $useSubScore = $this->round->getValidScoreConfig($this->competitionSport)->useSubScore();
        foreach ($this->getFilteredGames($games) as $game) {
            foreach ($game->getPlaces() as $gamePlace) {
                $finalScore = $this->scoreConfigService->getFinalTogetherScore($gamePlace);
                $performance = $performanceMap[$gamePlace->getPlace()->getRoundLocationId()];
                $performance->addGame();
                $performance->addPoints($finalScore);
                $performance->addScored($finalScore);
                if ($useSubScore) {
                    $finalSubScore = $this->scoreConfigService->getFinalTogetherSubScore($gamePlace);
                    $performance->addSubScored($finalSubScore);
                }
            }
        };
        return $performances;
    }

    /**
     * @param array<TogetherGame|AgainstGame> $games
     * @return array<TogetherGame>
     */
    protected function getFilteredGames(array $games): array
    {
        return array_filter(parent::getFilteredGames($games), function (AgainstGame | TogetherGame $game): bool {
            return $game instanceof TogetherGame;
        });
    }
}
