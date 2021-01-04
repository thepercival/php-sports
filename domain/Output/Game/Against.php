<?php
declare(strict_types=1);

namespace Sports\Output\Game;

use Psr\Log\LoggerInterface;
use Sports\Output\Game as OutputGame;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\Together as TogetherGame;
use Sports\Place\Location\Map as PlaceLocationMap;
use Sports\Ranking\ItemsGetter\Against as AgainstItemsGetter;
use Sports\State;
use Sports\Game;

class Against extends OutputGame
{
    public function __construct(PlaceLocationMap $placeLocationMap = null, LoggerInterface $logger = null)
    {
        parent::__construct($placeLocationMap, $logger);
    }

    protected function getDescriptionAsString($game): string
    {
        return $this->getPlacesAsString($game->getPlaces(AgainstGame::HOME))
            . ' ' . $this->getScoreAsString($game) . ' '
            . $this->getPlacesAsString($game->getPlaces(AgainstGame::AWAY));
    }

    /**
     * @param AgainstGame|TogetherGame $game
     * @return string
     */
    protected function getScoreAsString($game): string
    {
        $score = ' - ';
        if ($game->getState() !== State::Finished) {
            return $score;
        }
        $finalScore = $this->sportScoreConfigService->getFinalAgainstScore($game);
        if ($finalScore === null) {
            return $score;
        }
        $retVal = $finalScore->getHomeScore() . $score . $finalScore->getAwayScore();
        if ($game->getFinalPhase() === Game::PHASE_EXTRATIME) {
            $retVal .= ' nv';
        }
        while (strlen($retVal) < 10) {
            $retVal .=  ' ';
        }
        return $retVal;
    }

    /**
     * @param AgainstGame|TogetherGame $game
     * @return string
     */
    protected function getPointsAsString($game): string
    {
        $score = ' - ';
        if ($game->getState() !== State::Finished) {
            return $score;
        }
        $itemGetter = new AgainstItemsGetter($game->getRound(), State::Finished);
        $finalScore = $this->sportScoreConfigService->getFinalAgainstScore($game);
        if ($finalScore === null) {
            return $score;
        }
        $homePoints = $itemGetter->getNrOfPoints($finalScore, AgainstGame::HOME, $game);
        $awayPoints = $itemGetter->getNrOfPoints($finalScore, AgainstGame::AWAY, $game);
        return $homePoints . 'p' . $score . $awayPoints . 'p';
    }
}
