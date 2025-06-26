<?php

declare(strict_types=1);

namespace Sports\Score;

use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Score\AgainstScore as AgainstScore;
use Sports\Score\TogetherScore as TogetherScore;

final class Creator
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
     * @param list<AgainstScore> $newGameScores
     * @return void
     */
    public function addAgainstScores(AgainstGame $game, array $newGameScores): void
    {
        foreach ($newGameScores as $newGameScore) {
            new AgainstScore($game, $newGameScore->getHome(), $newGameScore->getAway(), $newGameScore->getPhase());
        }
    }

    /**
     * @param TogetherGamePlace $gamePlace
     * @param list<TogetherScore> $newScores
     * @return void
     */
    public function addTogetherScores(TogetherGamePlace $gamePlace, array $newScores): void
    {
        foreach ($newScores as $newScore) {
            new TogetherScore($gamePlace, $newScore->getScore(), $newScore->getPhase());
        }
    }
}
