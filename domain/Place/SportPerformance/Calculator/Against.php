<?php
declare(strict_types=1);

namespace Sports\Place\SportPerformance\Calculator;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Game\Phase as GamePhase;
use Sports\Score\Against as AgainstGameScore;
use Sports\Score\AgainstHelper as AgainstScoreHelper;
use Sports\Score\Against as AgainstScore;
use Sports\Place;
use Sports\Round;
use Sports\Place\SportPerformance;
use Sports\Place\SportPerformance\Calculator;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Together as TogetherGame;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Against\Result as AgainstResult;

class Against extends Calculator
{
    public function __construct(Round $round, CompetitionSport $competitionSport)
    {
        parent::__construct($round, $competitionSport);
    }

    /**
     * @param list<Place> $places
     * @param list<AgainstGame> $games
     * @return list<SportPerformance>
     */
    public function getPerformances(array $places, array $games): array
    {
        $performances = $this->createPerformances($places);
        $performanceMap = $this->getPerformanceMap($performances);
        $useSubScore = $this->round->getValidScoreConfig($this->competitionSport)->useSubScore();
        foreach ($this->getFilteredGames($games) as $game) {
            $finalScore = $this->scoreConfigService->getFinalAgainstScore($game);
            $finalSubScore = $useSubScore ? $this->scoreConfigService->getFinalAgainstSubScore($game) : null;
            foreach ([AgainstSide::HOME, AgainstSide::AWAY] as $side) {
                $points = $this->getNrOfPoints($finalScore, $side, $game);
                $scored = $this->getNrOfUnits($finalScore, $side, AgainstGameScore::SCORED);
                $received = $this->getNrOfUnits($finalScore, $side, AgainstGameScore::RECEIVED);
                $subScored = 0;
                $subReceived = 0;
                if ($useSubScore) {
                    $subScored = $this->getNrOfUnits($finalSubScore, $side, AgainstGameScore::SCORED);
                    $subReceived = $this->getNrOfUnits($finalSubScore, $side, AgainstGameScore::RECEIVED);
                }

                foreach ($game->getSidePlaces($side) as $gamePlace) {
                    $performance = $performanceMap[$gamePlace->getPlace()->getRoundLocationId()];
                    $performance->addGame();
                    $performance->addPoints($points);
                    $performance->addScored($scored);
                    $performance->addReceived($received);
                    $performance->addSubScored($subScored);
                    $performance->addSubReceived($subReceived);
                }
            }
        };
        return $performances;
    }

    public function getNrOfPoints(?AgainstScoreHelper $finalScore, int $side, AgainstGame $game): float
    {
        if ($finalScore === null) {
            return 0;
        }
        $againstQualifyConfig = $game->getAgainstQualifyConfig();
        if ($finalScore->getResult($side) === AgainstResult::WIN) {
            if ($game->getFinalPhase() === GamePhase::RegularTime) {
                return $againstQualifyConfig->getWinPoints();
            } elseif ($game->getFinalPhase() === GamePhase::ExtraTime) {
                return $againstQualifyConfig->getWinPointsExt();
            }
        } elseif ($finalScore->getResult($side) === AgainstResult::DRAW) {
            if ($game->getFinalPhase() === GamePhase::RegularTime) {
                return $againstQualifyConfig->getDrawPoints();
            } elseif ($game->getFinalPhase() === GamePhase::ExtraTime) {
                return $againstQualifyConfig->getDrawPointsExt();
            }
        } elseif ($game->getFinalPhase() === GamePhase::ExtraTime) {
            return $againstQualifyConfig->getLosePointsExt();
        }
        return 0;
    }

    private function getNrOfUnits(?AgainstScoreHelper $finalScore, int $side, int $scoredReceived): int
    {
        if ($finalScore === null) {
            return 0;
        }
        $opposite = $side === AgainstSide::HOME ? AgainstSide::AWAY : AgainstSide::HOME;
        return $this->getGameScorePart($finalScore, $scoredReceived === AgainstScore::SCORED ? $side : $opposite);
    }

    private function getGameScorePart(AgainstScoreHelper $againstGameScore, int $side): int
    {
        return $side === AgainstSide::HOME ? $againstGameScore->getHome() : $againstGameScore->getAway();
    }

    /**
     * @param list<TogetherGame|AgainstGame> $games
     * @return list<AgainstGame>
     */
    protected function getFilteredGames(array $games): array
    {
        /** @var list<AgainstGame> $againstGames */
        $againstGames = array_filter($games, function (AgainstGame | TogetherGame $game): bool {
            return $game instanceof AgainstGame && $this->competitionSport === $game->getCompetitionSport();
        });
        return array_values($againstGames);
    }
}
