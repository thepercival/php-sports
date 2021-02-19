<?php

namespace Sports\Score;

use Sports\Game\Against as AgainstGame;
use Sports\Score\Against as AgainstScore;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Score\Together as TogetherScore;

class Creator
{
    public function __construct()
    {
    }

//    public function editResource(Game $game, Field $field = null, Referee $referee = null, Place $refereePlace = null)
//    {
//        $game->setField($field);
//        $game->setReferee($referee);
//        $game->setRefereePlace($refereePlace);
//        return $game;
//    }

    /**
     * @param AgainstGame $game
     * @param array|AgainstScore[] $newGameScores
     */
    public function addAgainstScores(AgainstGame $game, array $newGameScores)
    {
        foreach ($newGameScores as $newGameScore) {
            new AgainstScore($game, $newGameScore->getHome(), $newGameScore->getAway(), $newGameScore->getPhase());
        }
    }

    /**
     * @param TogetherGamePlace $gamePlace
     * @param array|TogetherScore[] $newScores
     */
    public function addTogetherScores(TogetherGamePlace $gamePlace, array $newScores)
    {
        foreach ($newScores as $newScore) {
            new TogetherScore($gamePlace, $newScore->getScore(), $newScore->getPhase());
        }
    }
}
