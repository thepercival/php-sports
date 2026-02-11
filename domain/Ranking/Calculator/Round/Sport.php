<?php

declare(strict_types=1);

namespace Sports\Ranking\Calculator\Round;

use Closure;
use Sports\Game\State as GameState;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Against as AgainstGame;
use Sports\Place\SportPerformance\Calculator as PerformanceCalculator;
use Sports\Ranking\AgainstRuleSet;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use Sports\Poule;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Place;
use Sports\Ranking\Item\Round\Sport as SportRoundRankingItem;
use Sports\Ranking\Rule\Getter as RankingRuleGetter;
use Sports\Place\SportPerformance;
use Sports\Competition\CompetitionSport;
use Sports\Round;
use Sports\Ranking\Rule as RankingRule;

abstract class Sport
{
    /**
     * @var array<string, bool>
     */
    protected array $gameStateMap = [];
    /**
     * @var array<string, Closure(list<SportPerformance>):list<SportPerformance>>
     */
    protected array $rankFunctionMap = [];
    protected RankingRuleGetter $rankingRuleGetter;

    /**
     * @param CompetitionSport $competitionSport
     * @param list<GameState> $gameStates
     */
    public function __construct(protected CompetitionSport $competitionSport, array $gameStates)
    {
        foreach ($gameStates as $state) {
            $this->gameStateMap[$state->value] = true;
        }
        $this->rankingRuleGetter = new RankingRuleGetter();
    }

    /**
     * @param Poule $poule
     * @return list<SportRoundRankingItem>
     */
    abstract public function getItemsForPoule(Poule $poule): array;

    /**
     * @param list<AgainstGame|TogetherGame> $games
     * @return list<AgainstGame|TogetherGame>
     */
    protected function getFilteredGames(array $games): array
    {
        return array_values(array_filter($games, function (AgainstGame|TogetherGame $game): bool {
            return array_key_exists($game->getState()->value, $this->gameStateMap);
        }));
    }


//    /**
//     * @param MultipleQualifyRule $multipleRule
//     * @return list<PlaceLocation>
//     */
//    public function getPlaceLocationsForMultipleRule(MultipleQualifyRule $multipleRule): array
//    {
//        $sportRoundRankingItems = $this->getItemsForHorizontalPoule($multipleRule->getFromHorizontalPoule());
//
//        return array_map(function (SportRoundRankingItem $rankingItem): PlaceLocation {
//            return $rankingItem->getPlaceLocation();
//        }, $sportRoundRankingItems);
//    }
//
//    /**
//     * @param MultipleQualifyRule $multipleRule
//     * @return list<Place>
//     */
//    public function getPlacesForMultipleRule(MultipleQualifyRule $multipleRule): array
//    {
//        $fromRound = $multipleRule->getFromHorizontalPoule()->getRound();
//        $sportRankingItems = $this->getItemsForHorizontalPoule($multipleRule->getFromHorizontalPoule());
//        return array_values(
//            array_map(function (SportRoundRankingItem $rankingSportItem) use ($fromRound): Place {
//                return $fromRound->getPlace($rankingSportItem->getPerformance()->getPlace());
//            }, $sportRankingItems)
//        );
//    }
//
//    /**
//     * @param HorizontalPoule $horizontalPoule
//     * @return list<SportRoundRankingItem>
//     */
//    public function getItemsForHorizontalPoule(HorizontalPoule $horizontalPoule): array
//    {
//        $performances = [];
//        foreach ($horizontalPoule->getPlaces() as $place) {
//            $sportRankingItems = $this->getItemsForPoule($place->getPoule());
//            $sportRankingItem = $this->getItemByRank($sportRankingItems, $place->getPlaceNr());
//            if ($sportRankingItem === null) {
//                continue;
//            }
//            $performances[] = $sportRankingItem->getPerformance();
//        }
//        $scoreConfig = $horizontalPoule->getRound()->getValidScoreConfig($this->competitionSport);
//        $ruleSet = $this->competitionSport->getCompetition()->getAgainstRuleSet();
//        $rankingRules = $this->rankingRuleGetter->getRules($ruleSet, $scoreConfig->useSubScore());
//        return $this->rankItems($performances, $rankingRules);
//    }



    /**
     * @param list<SportPerformance> $originalPerformances
     * @param list<RankingRule> $rankingRules
     * @return list<SportRoundRankingItem>
     */
    protected function rankItems(array $originalPerformances, array $rankingRules): array
    {
        $performances = $originalPerformances;
        $sportRankingItems = [];
        $nrOfIterations = 0;
        while (count($performances) > 0) {
            $bestPerformances = $this->findBestPerformances($performances, $rankingRules);
            $rank = $nrOfIterations + 1;
            usort($bestPerformances, function (SportPerformance $perfA, SportPerformance $perfB): int {
                if ($perfA->getPlace()->getPouleNr() === $perfB->getPlace()->getPouleNr()) {
                    return $perfA->getPlace()->getPlaceNr() - $perfB->getPlace()->getPlaceNr();
                }
                return $perfA->getPlace()->getPouleNr() - $perfB->getPlace()->getPouleNr();
            });
            foreach ($bestPerformances as $bestPerformance) {
                $idx = array_search($bestPerformance, $performances, true);
                if ($idx === false) {
                    continue;
                }
                array_splice($performances, $idx, 1);
                $sportRankingItems[] = new SportRoundRankingItem($bestPerformance, ++$nrOfIterations, $rank);
            }
        }
        return $sportRankingItems;
    }

    /**
     * @param Round $round
     * @param list<Place> $places
     * @param list<AgainstGame|TogetherGame> $games
     * @return list<SportRoundRankingItem>
     */
    protected function getItems(Round $round, array $places, array $games): array
    {
        $calculator = $this->getCalculator($round);
        $performances = $calculator->getPerformances($places, $this->getFilteredGames($games));

        $scoreConfig = $round->getValidScoreConfig($this->competitionSport);

        $rankingRules = $this->rankingRuleGetter->getRules($this->getRuleSet(), $scoreConfig->useSubScore());
        return $this->rankItems($performances, $rankingRules);
    }

    protected function getRuleSet(): AgainstRuleSet|null
    {
        $sportVariant = $this->competitionSport->createVariant();
        if ($sportVariant instanceof AgainstSportVariant) {
            return $this->competitionSport->getCompetition()->getAgainstRuleSet();
        }
        return null;
    }

    abstract protected function getCalculator(Round $round): PerformanceCalculator;


    /**
     * @param list<SportRoundRankingItem> $rankingItems
     * @param int $rank
     * @return SportRoundRankingItem|null
     */
    public function getItemByRank(array $rankingItems, int $rank): ?SportRoundRankingItem
    {
        $filtered = array_filter($rankingItems, function (SportRoundRankingItem $rankingItem) use ($rank): bool {
            return $rankingItem->getUniqueRank() === $rank;
        });
        return count($filtered) > 0 ? reset($filtered) : null;
    }

    /**
     * @param list<SportPerformance> $originalPerformances
     * @param list<RankingRule> $rankingRules
     * @return list<SportPerformance>
     */
    protected function findBestPerformances(array $originalPerformances, array $rankingRules): array
    {
        $bestPerformances = $originalPerformances;
        foreach ($rankingRules as $rankingRule) {
            $rankingFunction = $this->rankFunctionMap[$rankingRule->name];
            if ($rankingRule === RankingRule::BestAmongEachOther && count($originalPerformances) === count($bestPerformances)) {
                continue;
            }
            $bestPerformances = $rankingFunction($bestPerformances);
            if (count($bestPerformances) < 2) {
                break;
            }
        }
        return $bestPerformances;
    }
}
