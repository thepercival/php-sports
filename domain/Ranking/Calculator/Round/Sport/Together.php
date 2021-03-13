<?php

declare(strict_types=1);

namespace Sports\Ranking\Calculator\Round\Sport;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Poule;
use Sports\Place;
use Sports\Round;
use Sports\Game\Together as TogetherGame;
use Sports\Ranking\Item\Round\Sport as SportRoundRankingItem;
use Sports\Place\SportPerformance\Calculator\Together as PlaceTogetherPerformanceCalculator;
use Sports\State;
use Sports\Ranking\Calculator\Round\Sport as SportRoundRankingCalculator;

class Together extends SportRoundRankingCalculator
{
    public function __construct(CompetitionSport $competitionSport, array $gameStates = null)
    {
        parent::__construct($competitionSport, $gameStates ?? [State::Finished]);
    }

    /**
     * @param Poule $poule
     * @return array<SportRoundRankingItem>
     */
    public function getItemsForPoule(Poule $poule): array
    {
        return $this->getItems($poule->getRound(), $poule->getPlaces()->toArray(), $poule->getTogetherGames()->toArray());
    }

    /**
     * @param Round $round
     * @param array<Place> $places
     * @param array<TogetherGame> $games
     * @return array<SportRoundRankingItem>
     */
    protected function getItems(Round $round, array $places, array $games): array
    {
        $calculator = new PlaceTogetherPerformanceCalculator($round, $this->competitionSport);
        $performances = $calculator->getPerformances($places, $this->getFilteredGames($games));
        return $this->getItemsHelper($round, $performances);
    }

    /**
     * @param array<TogetherGame> $games
     * @return array<TogetherGame>
     */
    protected function getFilteredGames(array $games): array
    {
        return array_filter($games, function (TogetherGame $game): bool {
            return array_key_exists($game->getState(), $this->gameStateMap);
        });
    }
}
