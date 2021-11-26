<?php
declare(strict_types=1);

namespace Sports\Output\Game;

use Psr\Log\LoggerInterface;
use Sports\Competitor\Map as CompetitorMap;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Phase as GamePhase;
use Sports\Output\Game as OutputGame;
use Sports\Place\SportPerformance\Calculator\Against as AgainstSportPerformanceCalculator;
use Sports\State;
use SportsHelpers\Against\Side as AgainstSide;

class Against extends OutputGame
{
    public function __construct(CompetitorMap $competitorMap = null, LoggerInterface $logger = null)
    {
        parent::__construct($competitorMap, $logger);
    }

    public function output(AgainstGame $game, string $prefix = null): void
    {
        $field = $game->getField();

        $this->logger->info(
            ($prefix !== null ? $prefix : '') .
            $game->getStartDateTime()->format("Y-m-d H:i") . " " .
            $this->getGameRoundNrAsString($game->getGameRoundNumber()) . " " .
            $this->getBatchNrAsString($game->getBatchNr()) . " " .
            'poule ' . $game->getPoule()->getStructureLocation()
            . ', ' . $this->getDescriptionAsString($game)
            . ' , ' . $this->getRefereeAsString($game)
            . ', ' . $this->getFieldAsString($field)
            . ', ' . $game->getCompetitionSport()->getSport()->getName()
            . ' ' . $this->getPointsAsString($game) . ' '
        );
    }

    protected function getDescriptionAsString(AgainstGame $game): string
    {
        return $this->getPlacesAsString($game->getSidePlaces(AgainstSide::HOME))
            . ' ' . $this->getScoreAsString($game) . ' '
            . $this->getPlacesAsString($game->getSidePlaces(AgainstSide::AWAY));
    }

    protected function getScoreAsString(AgainstGame $game): string
    {
        $score = ' - ';
        if ($game->getState() !== State::Finished) {
            return $score;
        }
        $finalScore = $this->scoreConfigService->getFinalAgainstScore($game);
        if ($finalScore === null) {
            return $score;
        }
        $retVal = $finalScore->getHome() . $score . $finalScore->getAway();
        if ($game->getFinalPhase() === GamePhase::ExtraTime) {
            $retVal .= ' nv';
        }
        while (strlen($retVal) < 10) {
            $retVal .=  ' ';
        }
        return $retVal;
    }

    protected function getPointsAsString(AgainstGame $game): string
    {
        $score = ' - ';
        if ($game->getState() !== State::Finished) {
            return $score;
        }
        $performanceCalculator = new AgainstSportPerformanceCalculator($game->getRound(), $game->getCompetitionSport());
        $finalScore = $this->scoreConfigService->getFinalAgainstScore($game);
        if ($finalScore === null) {
            return $score;
        }
        $homePoints = $performanceCalculator->getNrOfPoints($finalScore, AgainstSide::HOME, $game);
        $awayPoints = $performanceCalculator->getNrOfPoints($finalScore, AgainstSide::AWAY, $game);
        return $homePoints . 'p' . $score . $awayPoints . 'p';
    }
}
