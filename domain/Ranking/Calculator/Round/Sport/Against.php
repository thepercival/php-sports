<?php

declare(strict_types=1);

namespace Sports\Ranking\Calculator\Round\Sport;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Ranking\Calculator\Round\Sport as SportRoundRankingCalculator;
use Sports\Ranking\FunctionMapCreator\Against as AgainstRankingFunctionMapCreator;
use Sports\Place\SportPerformance\Calculator\Against as PlaceAgainstPerformanceCalculator;
use Sports\Ranking\Item\Round\Sport as SportRoundRankingItem;
use Sports\State;
use Sports\Round;
use Sports\Poule;
use Sports\Place;
use Sports\Game\Against as AgainstGame;
use SportsHelpers\Against\Side as AgainstSide;

class Against extends SportRoundRankingCalculator
{
    /**
     * @param CompetitionSport $competitionSport
     * @param list<int>|null $gameStates
     */
    public function __construct(CompetitionSport $competitionSport, array $gameStates = null)
    {
        parent::__construct($competitionSport, $gameStates ?? [State::Finished]);
        $functionMapCreator = new AgainstRankingFunctionMapCreator($competitionSport, $gameStates ?? [State::Finished]);
        $this->rankFunctionMap = $functionMapCreator->getMap();
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
            array_values($poule->getAgainstGames()->toArray()));
    }

    /**
     * @param Poule $poule
     * @param list<Place> $places
     * @return list<SportRoundRankingItem>
     */
    public function getItemsAmongPlaces(Poule $poule, array $places): array
    {
        $games = $this->getGamesAmongEachOther($places, array_values($poule->getAgainstGames()->toArray()));
        return $this->getItems($poule->getRound(), $places, $games);
    }

    /**
     * @param Round $round
     * @param list<Place> $places
     * @param list<AgainstGame> $games
     * @return list<SportRoundRankingItem>
     */
    protected function getItems(Round $round, array $places, array $games): array
    {
        $calculator = new PlaceAgainstPerformanceCalculator($round, $this->competitionSport);
        $performances = $calculator->getPerformances($places, $this->getFilteredGames($games));
        return $this->getItemsHelper($round, $performances);
    }

    /**
     * @param list<AgainstGame> $games
     * @return list<AgainstGame>
     */
    protected function getFilteredGames(array $games): array
    {
        return array_values(array_filter($games, function (AgainstGame $game): bool {
            return array_key_exists($game->getState(), $this->gameStateMap);
        }));
    }

    /**
     * @param list<Place> $places
     * @param list<AgainstGame> $games
     * @return list<AgainstGame>
     */
    private function getGamesAmongEachOther(array $places, array $games): array
    {
        $gamesRet = [];
        foreach ($games as $p_gameIt) {
            $inHome = false;
            foreach ($places as $place) {
                if ($p_gameIt->isParticipating($place, AgainstSide::HOME)) {
                    $inHome = true;
                    break;
                }
            }
            $inAway = false;
            foreach ($places as $place) {
                if ($p_gameIt->isParticipating($place, AgainstSide::AWAY)) {
                    $inAway = true;
                    break;
                }
            }
            if ($inHome && $inAway) {
                $gamesRet[] = $p_gameIt;
            }
        }
        return $gamesRet;
    }
}
