<?php
declare(strict_types=1);

namespace Sports\Output\Game;

use Psr\Log\LoggerInterface;
use Sports\Competitor\Map as CompetitorMap;
use Sports\Output\Game as OutputGame;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\State;

class Together extends OutputGame
{
    public function __construct(CompetitorMap $competitorMap = null, LoggerInterface $logger = null)
    {
        parent::__construct($competitorMap, $logger);
    }

    public function output(TogetherGame $game, string $prefix = null): void
    {
        $field = $game->getField();

        $this->logger->info(
            ($prefix !== null ? $prefix : '') .
            $game->getStartDateTime()->format("Y-m-d H:i") . " " .
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
        return $this->getPlacesAsString($game->getPlaces()->toArray())  . ' ' . $this->getScoreAsString($game) ;
    }

    protected function getScoreAsString(TogetherGame $game): string
    {
        return $this->getPointsAsString($game);
    }

    protected function getPointsAsString(TogetherGame $game): string
    {
        if ($game->getState() !== State::Finished) {
            return '';
        }
        return join(",", $game->getPlaces()->map(function (TogetherGamePlace $gamePlace): string {
            return (string)$this->scoreConfigService->getFinalTogetherScore($gamePlace);
        })->toArray());
    }
}
