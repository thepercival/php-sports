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
    /**
     * @param CompetitionSport $competitionSport
     * @param list<int>|null $gameStates
     */
    public function __construct(CompetitionSport $competitionSport, array $gameStates = null)
    {
        parent::__construct($competitionSport, $gameStates ?? [State::Finished]);
    }

    /**
     * @param Poule $poule
     * @return list<SportRoundRankingItem>
     */
    public function getItemsForPoule(Poule $poule): array
    {
        return $this->getItems(
            $poule->getRound(),
            array_values($poule->getPlaces()->toArray()),
            array_values($poule->getTogetherGames()->toArray()));
    }

    /**
     * @param Round $round
     * @param list<Place> $places
     * @param list<TogetherGame> $games
     * @return list<SportRoundRankingItem>
     */
    protected function getItems(Round $round, array $places, array $games): array
    {
        $calculator = new PlaceTogetherPerformanceCalculator($round, $this->competitionSport);
        $performances = $calculator->getPerformances($places, $this->getFilteredGames($games));
        return $this->getItemsHelper($round, $performances);
    }

    /**
     * @param list<TogetherGame> $games
     * @return list<TogetherGame>
     */
    protected function getFilteredGames(array $games): array
    {
        return array_values(array_filter($games, function (TogetherGame $game): bool {
            return array_key_exists($game->getState(), $this->gameStateMap);
        }));
    }
}
