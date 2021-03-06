<?php
declare(strict_types=1);

namespace Sports\Ranking\Calculator\Round\Sport;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Poule;
use Sports\Place;
use Sports\Round;
use Sports\Game\Together as TogetherGame;
use Sports\Ranking\Item\Round\Sport as SportRoundRankingItem;
use Sports\Ranking\FunctionMapCreator as RankingFunctionMapCreator;
use Sports\Place\SportPerformance\Calculator\Together as PlaceTogetherPerformanceCalculator;
use Sports\Place\SportPerformance\Calculator as PerformanceCalculator;
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
        $functionMapCreator = new RankingFunctionMapCreator();
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
            array_values($poule->getTogetherGames()->toArray()));
    }

    protected function getCalculator(Round $round): PerformanceCalculator
    {
        return new PlaceTogetherPerformanceCalculator($round, $this->competitionSport);
    }
}
