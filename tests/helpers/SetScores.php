<?php

declare(strict_types=1);

namespace Sports\TestHelper;

use Sports\Game\Against as AgainstGame;
use Sports\Game\Phase as GamePhase;
use Sports\Game\Place\Against as AgainstGamPlace;
use Sports\Game\GameState as GameState;
use Sports\Place;
use Sports\Poule;
use Sports\Score\AgainstScore as AgainstGameScore;
use SportsHelpers\Against\Side as AgainstSide;

trait SetScores
{
    protected function setAgainstScore(
        Poule $poule,
        int $homePlaceNr,
        int $awayPlaceNr,
        int $homeGoals,
        int $awayGoals,
        GameState $state = null,
        int $homeExtraPoints = 0,
        int $awayExtraPoints = 0
    ): void {
        $homePlace = $poule->getPlace($homePlaceNr);
        $awayPlace = $poule->getPlace($awayPlaceNr);
        $foundGames = array_filter(
            $poule->getAgainstGames()->toArray(),
            function (AgainstGame $game) use ($homePlace, $awayPlace): bool {
                $homePlaces = array_map(
                    function (AgainstGamPlace $gamePlace): Place {
                        return $gamePlace->getPlace();
                    },
                    $game->getSidePlaces(AgainstSide::Home)
                );
                $awayPlaces = array_map(
                    function (AgainstGamPlace $gamePlace): Place {
                        return $gamePlace->getPlace();
                    },
                    $game->getSidePlaces(AgainstSide::Away)
                );

                $homePlacesHasHomePlace = count(array_filter(
                    $homePlaces,
                    function (Place $homePlaceIt) use ($homePlace): bool {
                        return $homePlaceIt === $homePlace;
                    }
                )) > 0;
                $homePlacesHasAwayPlace = count(array_filter(
                    $homePlaces,
                    function (Place $homePlaceIt) use ($awayPlace): bool {
                        return $homePlaceIt === $awayPlace;
                    }
                )) > 0;
                $awayPlacesHasHomePlace = count(array_filter(
                    $awayPlaces,
                    function (Place $awayPlaceIt) use ($homePlace): bool {
                        return $awayPlaceIt === $homePlace;
                    }
                )) > 0;
                $awayPlacesHasAwayPlace = count(array_filter(
                    $awayPlaces,
                    function (Place $awayPlaceIt) use ($awayPlace): bool {
                        return $awayPlaceIt === $awayPlace;
                    }
                )) > 0;
                return ($homePlacesHasHomePlace && $awayPlacesHasAwayPlace) || ($homePlacesHasAwayPlace && $awayPlacesHasHomePlace);
            }
        );
        $foundGame = reset($foundGames);
        if ($foundGame === false) {
            throw new \Exception('de wedstrijd kan niet gevonden worden', E_ERROR);
        }
        $newHomeGoals = $foundGame->getSide($homePlace) === AgainstSide::Home ? $homeGoals : $awayGoals;
        $newAwayGoals = $foundGame->getSide($awayPlace) === AgainstSide::Away ? $awayGoals : $homeGoals;

        $foundGame->getScores()->add(
            new AgainstGameScore($foundGame, $newHomeGoals, $newAwayGoals, GamePhase::RegularTime)
        );
        $foundGame->setState($state !== null ? $state : GameState::Finished);
        $foundGame->setHomeExtraPoints($homeExtraPoints);
        $foundGame->setAwayExtraPoints($awayExtraPoints);
    }
}
