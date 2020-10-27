<?php

namespace Sports\Game\Score;

use League\Period\Period;
use Sports\Game\Repository as GameRepository;
use Sports\Game\Score\Repository as GameScoreRepository;
use Sports\Place;
use Sports\Game;
use Sports\Planning\GameGenerator;
use Sports\Planning\Input;
use Sports\Referee;
use Sports\Field;
use Sports\Game\Score as GameScore;
use Sports\Round\Number as RoundNumber;

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
     * @param Game $game
     * @param array|GameScore[] $newGameScores
     */
    public function addScores(Game $game, array $newGameScores)
    {
        foreach ($newGameScores as $newGameScore) {
            new GameScore($game, $newGameScore->getHome(), $newGameScore->getAway(), $newGameScore->getPhase());
        }
    }
}
