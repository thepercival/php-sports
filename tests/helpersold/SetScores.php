<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 12-6-2019
 * Time: 11:10
 */

use Sports\Poule;
use Sports\Game;
use Sports\Game\Place as GamPlace;
use Sports\Game\Score as GameScore;
use Sports\State;

function setScoreSingle(Poule $poule, int $homePlaceNr, int $awayPlaceNr, int $homeGoals, int $awayGoals, int $state = null)
{
    $homePlace = $poule->getPlace($homePlaceNr);
    $awayPlace = $poule->getPlace($awayPlaceNr);
    $foundGames = $poule->getGames()->filter(function (Game $game) use ($homePlace, $awayPlace) {
        $homePlaces = $game->getPlaces(Game::HOME)->map(function (GamPlace $gamePlace) {
            return $gamePlace->getPlace();
        });
        $awayPlaces = $game->getPlaces(Game::AWAY)->map(function (GamPlace $gamePlace) {
            return $gamePlace->getPlace();
        });

        $homePlacesHasHomePlace = $homePlaces->filter(function ($homePlaceIt) use ($homePlace) {
            return $homePlaceIt === $homePlace;
        })->count() > 0;
        $homePlacesHasAwayPlace = $homePlaces->filter(function ($homePlaceIt) use ($awayPlace) {
            return $homePlaceIt === $awayPlace;
        })->count() > 0;
        $awayPlacesHasHomePlace = $awayPlaces->filter(function ($awayPlaceIt) use ($homePlace) {
            return $awayPlaceIt === $homePlace;
        })->count() > 0;
        $awayPlacesHasAwayPlace = $awayPlaces->filter(function ($awayPlaceIt) use ($awayPlace) {
            return $awayPlaceIt === $awayPlace;
        })->count() > 0;
        return ($homePlacesHasHomePlace && $awayPlacesHasAwayPlace) || ($homePlacesHasAwayPlace && $awayPlacesHasHomePlace);
    });
    $foundGame = $foundGames->first();
    $newHomeGoals = $foundGame->getHomeAway($homePlace) === Game::HOME ? $homeGoals : $awayGoals;
    $newAwayGoals = $foundGame->getHomeAway($awayPlace) === Game::AWAY ? $awayGoals : $homeGoals;

    $foundGame->getScores()->add(new GameScore($foundGame, $newHomeGoals, $newAwayGoals, Game::PHASE_REGULARTIME));
    $foundGame->setState($state ? $state : State::Finished);
}
