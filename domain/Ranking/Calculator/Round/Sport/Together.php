<?php

declare(strict_types=1);

namespace Sports\Ranking\Calculator\Round\Sport;

use Sports\Competition\CompetitionSport as CompetitionSport;
use Sports\Poule;
use Sports\Place;
use Sports\Round;
use Sports\Game\Together as TogetherGame;
use Sports\Ranking\Item\Round\Sport as SportRoundRankingItem;
use Sports\Ranking\FunctionMapCreator as RankingFunctionMapCreator;
use Sports\Place\SportPerformance\Calculator\Together as PlaceTogetherPerformanceCalculator;
use Sports\Place\SportPerformance\Calculator as PerformanceCalculator;
use Sports\Game\State as GameState;
use Sports\Ranking\Calculator\Round\Sport as SportRoundRankingCalculator;

final class Together extends SportRoundRankingCalculator
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
            array_values($poule->getTogetherGames()->toArray())
        );
    }

    #[\Override]
    protected function getCalculator(Round $round): PerformanceCalculator
    {
        return new PlaceTogetherPerformanceCalculator($round, $this->competitionSport);
    }
}
