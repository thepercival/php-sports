<?php

declare(strict_types=1);

namespace Sports\Ranking\Calculator\Round;

use Sports\Competition\CompetitionSport as CompetitionSport;
use Sports\Game\GameState as GameState;
use Sports\Place\SportPerformance\Calculator as PerformanceCalculator;
use Sports\Place\SportPerformance\Calculator\Together as PlaceTogetherPerformanceCalculator;
use Sports\Poule;
use Sports\Ranking\FunctionMapCreator as RankingFunctionMapCreator;
use Sports\Ranking\Item\RoundRankingItemForSport as SportRoundRankingItem;
use Sports\Round;

final class RoundRankingCalculatorForTogetherSport extends RoundRankingCalculatorForSportAbstract
{
    /**
     * @param CompetitionSport $competitionSport
     * @param list<GameState>|null $gameStates
     */
    public function __construct(CompetitionSport $competitionSport, array $gameStates = null)
    {
        parent::__construct($competitionSport, $gameStates ?? [GameState::Finished]);
        $functionMapCreator = new RankingFunctionMapCreator();
        $this->rankFunctionMap = $functionMapCreator->getMap();
    }

    #[\Override]
    public function getItemsForPoule(Poule $poule): array
    {
        return $this->getItems(
            $poule->getRound(),
            array_values($poule->getPlaces()->toArray()),
            array_values($poule->getTogetherGames()->toArray())
        );
    }

    #[\Override]
    protected function getCalculator(Round $round): PerformanceCalculator
    {
        return new PlaceTogetherPerformanceCalculator($round, $this->competitionSport);
    }
}
