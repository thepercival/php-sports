<?php

declare(strict_types=1);

namespace Sports\Output\Game;

use Psr\Log\LoggerInterface;
use Sports\Competitor\Map as CompetitorMap;
use Sports\Competitor\StartLocationMap;
use Sports\Output\Game as OutputGame;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Game\GameState as GameState;

final class Together extends OutputGame
{
    public function __construct(StartLocationMap $startLocationMap = null, LoggerInterface $logger = null)
    {
        parent::__construct($startLocationMap, $logger);
    }

    public function output(TogetherGame $game, string $prefix = null): void
    {
        $field = $game->getField();

        $this->logger->info(
            ($prefix !== null ? $prefix : '') .
            $game->getStartDateTime()->format("Y-m-d H:i") . ' ' .
            ' - ' . // gameRoundNumber
            $this->getBatchNrAsString($game->getBatchNr()) . " " .
            'poule ' . $game->getPoule()->getNumber()
            . ', ' . $this->getDescriptionAsString($game)
            . ' , ' . $this->getRefereeAsString($game)
            . ', ' . $this->getFieldAsString($field)
            . ', ' . $game->getCompetitionSport()->getSport()->getName()
            . ' ' . $this->getPointsAsString($game) . ' '
        );
    }

    protected function getDescriptionAsString(TogetherGame $game): string
    {
        $places = array_values($game->getPlaces()->toArray());
        return $this->getPlacesAsString($places)  . ' ' . $this->getScoreAsString($game) ;
    }

    protected function getScoreAsString(TogetherGame $game): string
    {
        return $this->getPointsAsString($game);
    }

    protected function getPointsAsString(TogetherGame $game): string
    {
        if ($game->getState() !== GameState::Finished) {
            return '';
        }
        return join(",", array_map(function (TogetherGamePlace $gamePlace): string {
            return (string)$this->scoreConfigService->getFinalTogetherScore($gamePlace);
        }, $game->getPlaces()->toArray()));
    }
}
