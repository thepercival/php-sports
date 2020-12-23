<?php

namespace Sports\Score;

use Sports\Game\Against as AgainstGame;
use Sports\Score\Against as AgainstScore;

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
            new AgainstScore($game, $newGameScore->getHomeScore(), $newGameScore->getAwayScore(), $newGameScore->getPhase());
        }
    }
}
