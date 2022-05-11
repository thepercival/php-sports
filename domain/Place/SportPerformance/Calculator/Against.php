<?php

declare(strict_types=1);

namespace Sports\Place\SportPerformance\Calculator;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Phase as GamePhase;
use Sports\Game\Together as TogetherGame;
use Sports\Place;
use Sports\Place\SportPerformance;
use Sports\Place\SportPerformance\Calculator;
use Sports\Ranking\PointsCalculation;
use Sports\Round;
use Sports\Score\Against as AgainstGameScore;
use Sports\Score\Against as AgainstScore;
use Sports\Score\AgainstHelper as AgainstScoreHelper;
use SportsHelpers\Against\Result as AgainstResult;
use SportsHelpers\Against\Side as AgainstSide;

class Against extends Calculator
{
    public function __construct(Round $round, CompetitionSport $competitionSport)
    {
        parent::__construct($round, $competitionSport);
    }

    /**
     * @param list<Place> $places
     * @param list<AgainstGame|TogetherGame> $games
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
            foreach ([AgainstSide::Home, AgainstSide::Away] as $side) {
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
                    $roundLocationId = $gamePlace->getPlace()->getRoundLocationId();
                    if (!isset($performanceMap[$roundLocationId])) {
                        continue;
                    }
                    $performance = $performanceMap[$roundLocationId];
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

    public function getNrOfPoints(?AgainstScoreHelper $finalScore, AgainstSide $side, AgainstGame $game): float
    {
        if ($finalScore === null) {
            return 0;
        }
        $points = 0;
        $againstQualifyConfig = $game->getAgainstQualifyConfig();
        if ($againstQualifyConfig->getPointsCalculation() === PointsCalculation::AgainstGamePoints
            || $againstQualifyConfig->getPointsCalculation() === PointsCalculation::Both) {
            if ($finalScore->getResult($side) === AgainstResult::Win) {
                if ($game->getFinalPhase() === GamePhase::RegularTime) {
                    $points = $againstQualifyConfig->getWinPoints();
                } elseif ($game->getFinalPhase() === GamePhase::ExtraTime) {
                    $points = $againstQualifyConfig->getWinPointsExt();
                }
            } elseif ($finalScore->getResult($side) === AgainstResult::Draw) {
                if ($game->getFinalPhase() === GamePhase::RegularTime) {
                    $points = $againstQualifyConfig->getDrawPoints();
                } elseif ($game->getFinalPhase() === GamePhase::ExtraTime) {
                    $points = $againstQualifyConfig->getDrawPointsExt();
                }
            } elseif ($game->getFinalPhase() === GamePhase::ExtraTime) {
                $points = $againstQualifyConfig->getLosePointsExt();
            }
        }

        $againstQualifyConfig = $game->getAgainstQualifyConfig();
        if ($againstQualifyConfig->getPointsCalculation() === PointsCalculation::Scores
            || $againstQualifyConfig->getPointsCalculation() === PointsCalculation::Both) {
            $points += $finalScore->get($side);
        }

        return $points;
    }

    private function getNrOfUnits(?AgainstScoreHelper $finalScore, AgainstSide $side, int $scoredReceived): int
    {
        if ($finalScore === null) {
            return 0;
        }
        $opposite = $side->getOpposite();
        return $this->getGameScorePart($finalScore, $scoredReceived === AgainstScore::SCORED ? $side : $opposite);
    }

    private function getGameScorePart(AgainstScoreHelper $againstGameScore, AgainstSide $side): int
    {
        return $side === AgainstSide::Home ? $againstGameScore->getHome() : $againstGameScore->getAway();
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
        return $againstGames;
    }
}
