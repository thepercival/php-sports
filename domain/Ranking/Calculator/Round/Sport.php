<?php

declare(strict_types=1);

namespace Sports\Ranking\Calculator\Round;

use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use Sports\Poule;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Place;
use Sports\Ranking\Item\Round\Sport as SportRoundRankingItem;
use Sports\Ranking\Rule\Getter as RankingRuleGetter;
use Sports\Place\SportPerformance;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Round;
use Sports\Ranking\Rule as RankingRule;

abstract class Sport
{
    /**
     * @var array<bool>
     */
    protected array $gameStateMap = [];
    protected array $rankFunctionMap = [];
    protected RankingRuleGetter $rankingRuleGetter;

    public function __construct(protected CompetitionSport $competitionSport, array $gameStates)
    {
        foreach ($gameStates as $state) {
            $this->gameStateMap[$state] = true;
        }
        $this->rankingRuleGetter = new RankingRuleGetter();
    }

    /**
     * @param Poule $poule
     * @return array<SportRoundRankingItem>
     */
    abstract public function getItemsForPoule(Poule $poule): array;


//    public function getPlaceLocationsForHorizontalPoule(HorizontalPoule $horizontalPoule): array
//    {
//        return array_map(function (SportRoundRankingItem $rankingItem): PlaceLocation {
//            return $rankingItem->getPlaceLocation();
//        }, $this->getItemsForHorizontalPoule($horizontalPoule, true));
//    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @return array<Place>
     */
    public function getPlacesForHorizontalPoule(HorizontalPoule $horizontalPoule): array
    {
        return array_map(function (SportRoundRankingItem $rankingSportItem) use ($horizontalPoule): Place {
            return $horizontalPoule->getRound()->getPlace($rankingSportItem->getPerformance()->getPlace());
        }, $this->getItemsForHorizontalPoule($horizontalPoule, true));
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @param bool|null $checkOnSingleQualifyRule
     * @return array<SportRoundRankingItem>
     */
    public function getItemsForHorizontalPoule(HorizontalPoule $horizontalPoule, bool $checkOnSingleQualifyRule = null): array
    {
        /** @var array<SportPerformance> $performances */
        $performances = [];
        foreach ($horizontalPoule->getPlaces() as $place) {
            if ($checkOnSingleQualifyRule && $this->hasPlaceSingleQualifyRule($place)) {
                continue;
            }
            /** @var array<SportRoundRankingItem> $sportRankingItems */
            $sportRankingItems = $this->getItemsForPoule($place->getPoule());
            $sportRankingItem = $this->getItemByRank($sportRankingItems, $place->getNumber());
            if ($sportRankingItem === null) {
                continue;
            }
            $performances[] = $sportRankingItem->getPerformance();
        }
        $scoreConfig = $horizontalPoule->getRound()->getValidScoreConfig($this->competitionSport);
        $ruleSet = $this->competitionSport->getCompetition()->getRankingRuleSet();
        $rankingRules = $this->rankingRuleGetter->getRules($ruleSet, $scoreConfig->useSubScore());
        return $this->rankItems($performances, $rankingRules);
    }



    /**
     * @param array<SportPerformance> $originalPerformances
     * @param array<int> $rankingRules
     * @return array<SportRoundRankingItem>
     */
    protected function rankItems(array $originalPerformances, array $rankingRules): array
    {
        $performances = $originalPerformances;
        /** @var array<SportRoundRankingItem> $sportRankingItems */
        $sportRankingItems = [];
        $nrOfIterations = 0;
        while (count($performances) > 0) {
            $bestPerformances = $this->findBestPerformances($performances, $rankingRules);
            $rank = $nrOfIterations + 1;
            uasort($bestPerformances, function (SportPerformance $perfA, SportPerformance $perfB): int {
                if ($perfA->getPlace()->getPouleNr() === $perfB->getPlace()->getPouleNr()) {
                    return $perfA->getPlace()->getPlaceNr() - $perfB->getPlace()->getPlaceNr();
                }
                return $perfA->getPlace()->getPouleNr() - $perfB->getPlace()->getPouleNr();
            });
            foreach ($bestPerformances as $bestPerformance) {
                array_splice($performances, array_search($bestPerformance, $performances, true), 1);
                $sportRankingItems[] = new SportRoundRankingItem($bestPerformance, ++$nrOfIterations, $rank);
            }
        }
        return $sportRankingItems;
    }

    /**
     * @param Round $round
     * @param array<SportPerformance> $performances
     * @return array<SportRoundRankingItem>
     */
    protected function getItemsHelper(Round $round, array $performances): array
    {
        $scoreConfig = $round->getValidScoreConfig($this->competitionSport);
        $ruleSet = $this->competitionSport->getCompetition()->getRankingRuleSet();
        $rankingRules = $this->rankingRuleGetter->getRules($ruleSet, $scoreConfig->useSubScore());
        return $this->rankItems($performances, $rankingRules);
    }

    /**
     * @param array<SportRoundRankingItem> $rankingItems
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
     * @param array<SportPerformance> $originalPerformances
     * @param array<int> $rankingRules
     * @return array<SportPerformance>
     */
    protected function findBestPerformances(array $originalPerformances, array $rankingRules): array
    {
        $bestPerformances = $originalPerformances;
        foreach ($rankingRules as $rankingRule) {
            $rankingFunction = $this->rankFunctionMap[$rankingRule];
            if ($rankingRule === RankingRule::BestAmongEachOther && count($originalPerformances) === count($bestPerformances)) {
                break;
            }
            $bestPerformances = $rankingFunction($bestPerformances);
            if (count($bestPerformances) >= 2) {
                break;
            }
        }
        return $bestPerformances;
    }

    // Place can have a multiple and a single rule, if so than do not process place for horizontalpoule(multiple)
    protected function hasPlaceSingleQualifyRule(Place $place): bool
    {
        return count(array_filter($place->getToQualifyRules(), function (SingleQualifyRule|MultipleQualifyRule $qualifyRuleIt): bool {
            return ($qualifyRuleIt instanceof SingleQualifyRule);
        })) > 0;
    }
}
