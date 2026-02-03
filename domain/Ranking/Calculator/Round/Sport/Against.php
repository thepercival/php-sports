<?php

declare(strict_types=1);

namespace Sports\Ranking\Calculator\Round\Sport;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Game\Against as AgainstGame;
use Sports\Game\State as GameState;
use Sports\Place;
use Sports\Place\SportPerformance\Calculator as PerformanceCalculator;
use Sports\Place\SportPerformance\Calculator\Against as AgainstPerformanceCalculator;
use Sports\Poule;
use Sports\Ranking\Calculator\Round\Sport as SportRoundRankingCalculator;
use Sports\Ranking\FunctionMapCreator\Against as AgainstRankingFunctionMapCreator;
use Sports\Ranking\Item\Round\Sport as SportRoundRankingItem;
use Sports\Round;
use SportsHelpers\Against\AgainstSide;

final class Against extends SportRoundRankingCalculator
{
    /**
     * @param CompetitionSport $competitionSport
     * @param list<GameState>|null $gameStates
     */
    public function __construct(CompetitionSport $competitionSport, array|null $gameStates = null)
    {
        parent::__construct($competitionSport, $gameStates ?? [GameState::Finished]);
        $functionMapCreator = new AgainstRankingFunctionMapCreator($competitionSport, $gameStates ?? [GameState::Finished]);
        $this->rankFunctionMap = $functionMapCreator->getMap();
    }

    /**
     * @param Poule $poule
     * @return list<SportRoundRankingItem>
     */
    #[\Override]
    public function getItemsForPoule(Poule $poule): array
    {
        return $this->getItems(
            $poule->getRound(),
            array_values($poule->getPlaces()->toArray()),
            array_values($poule->getAgainstGames()->toArray())
        );
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

    #[\Override]
    protected function getCalculator(Round $round): PerformanceCalculator
    {
        return new AgainstPerformanceCalculator($round, $this->competitionSport);
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
                if ($p_gameIt->isParticipating($place, AgainstSide::Home)) {
                    $inHome = true;
                    break;
                }
            }
            $inAway = false;
            foreach ($places as $place) {
                if ($p_gameIt->isParticipating($place, AgainstSide::Away)) {
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
