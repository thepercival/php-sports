<?php

namespace Sports\TestHelper;

use Sports\Poule;
use Sports\Place;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamPlace;
use Sports\Score\Against as AgainstGameScore;
use SportsHelpers\Against\Side as AgainstSide;
use Sports\State;

trait SetScores
{
    protected function setScoreSingle(Poule $poule, int $homePlaceNr, int $awayPlaceNr, int $homeGoals, int $awayGoals, int $state = null)
    {
        $homePlace = $poule->getPlace($homePlaceNr);
        $awayPlace = $poule->getPlace($awayPlaceNr);
        $foundGames = $poule->getGames()->filter(function (AgainstGame $game) use ($homePlace, $awayPlace) {
            $homePlaces = $game->getPlaces(AgainstSide::HOME)->map(
                function (AgainstGamPlace $gamePlace): Place {
                    return $gamePlace->getPlace();
                }
            );
            $awayPlaces = $game->getPlaces(AgainstSide::AWAY)->map(
                function (AgainstGamPlace $gamePlace): Place {
                    return $gamePlace->getPlace();
                }
            );

            $homePlacesHasHomePlace = $homePlaces->filter(
                function ($homePlaceIt) use ($homePlace): bool {
                    return $homePlaceIt === $homePlace;
                }
            )->count() > 0;
            $homePlacesHasAwayPlace = $homePlaces->filter(
                function ($homePlaceIt) use ($awayPlace): bool {
                    return $homePlaceIt === $awayPlace;
                }
            )->count() > 0;
            $awayPlacesHasHomePlace = $awayPlaces->filter(
                function ($awayPlaceIt) use ($homePlace): bool {
                    return $awayPlaceIt === $homePlace;
                }
            )->count() > 0;
            $awayPlacesHasAwayPlace = $awayPlaces->filter(
                function ($awayPlaceIt) use ($awayPlace): bool {
                    return $awayPlaceIt === $awayPlace;
                }
            )->count() > 0;
            return ($homePlacesHasHomePlace && $awayPlacesHasAwayPlace) || ($homePlacesHasAwayPlace && $awayPlacesHasHomePlace);
        });
        $foundGame = $foundGames->first();
        $newHomeGoals = $foundGame->getSide($homePlace) === AgainstSide::HOME ? $homeGoals : $awayGoals;
        $newAwayGoals = $foundGame->getSide($awayPlace) === AgainstSide::AWAY ? $awayGoals : $homeGoals;

        $foundGame->getScores()->add(new AgainstGameScore($foundGame, $newHomeGoals, $newAwayGoals, AgainstGame::PHASE_REGULARTIME));
        $foundGame->setState($state !== null ? $state : State::Finished);
    }
}
