<?php

declare(strict_types=1);

namespace Sports\Place\SportPerformance\Calculator;

use Sports\Competition\CompetitionSport;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Together as TogetherGame;
use Sports\Place;
use Sports\Place\SportPerformance;
use Sports\Place\SportPerformance\Calculator;
use Sports\Round;

final class Together extends Calculator
{
    public function __construct(Round $round, CompetitionSport $competitionSport)
    {
        parent::__construct($round, $competitionSport);
    }

    /**
     * @param list<Place> $places
     * @param list<TogetherGame|AgainstGame> $games
     * @return list<SportPerformance>
     */
    #[\Override]
    public function getPerformances(array $places, array $games): array
    {
        $performances = $this->createPerformances($places);
        $performanceMap = $this->getPerformanceMap($performances);
        $useSubScore = $this->round->getValidScoreConfig($this->competitionSport)->useSubScore();
        foreach ($this->getFilteredGames($games) as $game) {
            foreach ($game->getPlaces() as $gamePlace) {
                $finalScore = $this->scoreConfigService->getFinalTogetherScore($gamePlace);
                $performance = $performanceMap[$gamePlace->getPlace()->getUniqueIndex()];
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
     * @param list<TogetherGame|AgainstGame> $games
     * @return list<TogetherGame>
     */
    protected function getFilteredGames(array $games): array
    {
        /** @var list<TogetherGame> $togetherGames */
        $togetherGames = array_filter($games, function (AgainstGame | TogetherGame $game): bool {
            return $game instanceof TogetherGame && $this->competitionSport === $game->getCompetitionSport();
        });
        return $togetherGames;
    }
}
