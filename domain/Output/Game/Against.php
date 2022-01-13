<?php

declare(strict_types=1);

namespace Sports\Output\Game;

use Psr\Log\LoggerInterface;
use Sports\Competitor\Map as CompetitorMap;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Phase as GamePhase;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\State as GameState;
use Sports\Output\Game as OutputGame;
use Sports\Place\SportPerformance\Calculator\Against as AgainstSportPerformanceCalculator;
use SportsHelpers\Against\Side;
use SportsHelpers\Against\Side as AgainstSide;

class Against extends OutputGame
{
    public function __construct(CompetitorMap $competitorMap = null, LoggerInterface $logger = null)
    {
        parent::__construct($competitorMap, $logger);
    }

    /**
     * @param AgainstGame $game
     * @param string|null $prefix
     * @param list<Column>|null $columns
     */
    public function output(AgainstGame $game, string $prefix = null, array $columns = null): void
    {
        if ($columns === null) {
            $columns = $this->getDefaultColumns();
        }
        $sumColumns = Column::sum($columns);
        $field = $game->getField();

        $content = ($prefix !== null ? $prefix : '');
        if (Column::has($sumColumns, Column::State)) {
            $content .= $game->getState()->name . ' ';
        }
        if (Column::has($sumColumns, Column::StartDateTime)) {
            $content .= $game->getStartDateTime()->format("Y-m-d H:i") . ' ';
        }
        if (Column::has($sumColumns, Column::GameRoundNumber)) {
            $content .= $this->getGameRoundNrAsString($game->getGameRoundNumber()) . ' ';
        }
        if (Column::has($sumColumns, Column::BatchNr)) {
            $content .= $this->getBatchNrAsString($game->getBatchNr()) . ' ';
        }
        if (Column::has($sumColumns, Column::Poule)) {
            $content .= 'poule ' . $game->getPoule()->getStructureLocation();
        }
        if (Column::has($sumColumns, Column::ScoreAndPlaces)) {
            $content .= ', ' . $this->getScoreAndPlacesAsString($game);
        }
        if (Column::has($sumColumns, Column::Referee)) {
            $content .= ' , ' . $this->getRefereeAsString($game);
        }
        if (Column::has($sumColumns, Column::Field)) {
            $content .= ', ' . $this->getFieldAsString($field);
        }
        if (Column::has($sumColumns, Column::Sport)) {
            $content .= ', ' . $game->getCompetitionSport()->getSport()->getName();
        }
        if (Column::has($sumColumns, Column::Points)) {
            $content .= ' , ' . $this->getPointsAsString($game);
        }
        if (Column::has($sumColumns, Column::ScoresLineupsAndEvents)) {
            $content .= ' , ' . $this->getScoresLineupsAndEventsAsString($game);
        }
        $this->logger->info($content . ' ');
    }

    protected function getScoreAndPlacesAsString(AgainstGame $game): string
    {
        return $this->getPlacesAsString($game->getSidePlaces(AgainstSide::Home))
            . ' ' . $this->getScoreAsString($game) . ' '
            . $this->getPlacesAsString($game->getSidePlaces(AgainstSide::Away));
    }

    protected function getScoreAsString(AgainstGame $game): string
    {
        $score = ' - ';
        if ($game->getState() !== GameState::Finished) {
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
        if ($game->getState() !== GameState::Finished) {
            return $score;
        }
        $performanceCalculator = new AgainstSportPerformanceCalculator($game->getRound(), $game->getCompetitionSport());
        $finalScore = $this->scoreConfigService->getFinalAgainstScore($game);
        if ($finalScore === null) {
            return $score;
        }
        $homePoints = $performanceCalculator->getNrOfPoints($finalScore, AgainstSide::Home, $game);
        $awayPoints = $performanceCalculator->getNrOfPoints($finalScore, AgainstSide::Away, $game);
        return $homePoints . 'p' . $score . $awayPoints . 'p';
    }

    protected function getScoresLineupsAndEventsAsString(AgainstGame $game): string
    {
        $homePlaces = $game->getSidePlaces(Side::Home);
        $homeContent = 'H('.$this->getSideScoresLineupsAndEventsAsString($homePlaces).')';
        $awayPlaces = $game->getSidePlaces(Side::Away);
        $awayContent = 'A('.$this->getSideScoresLineupsAndEventsAsString($awayPlaces).')';
        return $homeContent . ' ' . $awayContent;
    }

    /**
     * @param list<AgainstGamePlace> $gamePlaces
     * @return string
     */
    protected function getSideScoresLineupsAndEventsAsString(array $gamePlaces): string
    {
        $nrOfLineups = 0;
        $nrOfGoalEvents = 0;
        $nrOfOtherEvents = 0;
        foreach ($gamePlaces as $gamePlace) {
            $nrOfLineups += count($gamePlace->getLineup());
            $nrOfGoalEvents += count($gamePlace->getGoalEvents());
            $nrOfOtherEvents += count($gamePlace->getCardEvents()) + count($gamePlace->getSubstituteEvents());
        }
        return 'L:'.$nrOfLineups.' G:'.$nrOfGoalEvents.' E:'.$nrOfOtherEvents;
    }

    /**
     * @return list<Column>
     */
    protected function getDefaultColumns(): array
    {
        return [
            Column::StartDateTime,
            Column::BatchNr,
            Column::Poule,
            Column::ScoreAndPlaces,
            Column::Referee,
            Column::Field,
            Column::Sport
        ];
    }
}
