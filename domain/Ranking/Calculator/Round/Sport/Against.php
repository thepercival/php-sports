<?php
declare(strict_types=1);

namespace Sports\Ranking\Calculator\Round\Sport;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Ranking\Calculator\Round\Sport as SportRoundRankingCalculator;
use Sports\Ranking\FunctionMapCreator\Against as AgainstRankingFunctionMapCreator;
use Sports\Place\SportPerformance\Calculator\Against as AgainstPerformanceCalculator;
use Sports\Place\SportPerformance\Calculator as PerformanceCalculator;
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
