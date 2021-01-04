<?php
declare(strict_types=1);

namespace Sports\Output\Game;

use Psr\Log\LoggerInterface;
use Sports\Output\Game as OutputGame;
use Sports\Place\Location\Map as PlaceLocationMap;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\State;

class Together extends OutputGame
{
    public function __construct(PlaceLocationMap $placeLocationMap = null, LoggerInterface $logger = null)
    {
        parent::__construct($placeLocationMap, $logger);
    }

    protected function getDescriptionAsString($game): string
    {
        return $this->getPlacesAsString($game->getPlaces())  . ' ' . $this->getScoreAsString($game) ;
    }

    /**
     * @param AgainstGame|TogetherGame $game
     * @return string
     */
    protected function getScoreAsString($game): string
    {
        return $this->getPointsAsString($game);
    }

    /**
     * @param AgainstGame|TogetherGame $game
     * @return string
     */
    protected function getPointsAsString($game): string
    {
        if ($game->getState() !== State::Finished) {
            return '';
        }
        return join(",", $game->getPlaces()->map(function (TogetherGamePlace $gamePlace): string {
            return (string)$this->sportScoreConfigService->getFinalTogetherScore($gamePlace);
        })->toArray());
    }
}
