<?php
declare(strict_types=1);

namespace Sports\Ranking\Calculator\Round;

use Closure;
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
     * @var array<int, bool>
     */
    protected array $gameStateMap = [];
    /**
     * @var array<int, Closure(list<SportPerformance>):list<SportPerformance>>
     */
    protected array $rankFunctionMap = [];
    protected RankingRuleGetter $rankingRuleGetter;

    /**
     * @param CompetitionSport $competitionSport
     * @param list<int> $gameStates
     */
    public function __construct(protected CompetitionSport $competitionSport, array $gameStates)
    {
        foreach ($gameStates as $state) {
            $this->gameStateMap[$state] = true;
        }
        $this->rankingRuleGetter = new RankingRuleGetter();
    }

    /**
     * @param Poule $poule
     * @return list<SportRoundRankingItem>
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
     * @return list<Place|null>
     */
    public function getPlacesForHorizontalPoule(HorizontalPoule $horizontalPoule): array
    {
        return array_values(
            array_map(function (SportRoundRankingItem $rankingSportItem) use ($horizontalPoule): Place|null {
                return $horizontalPoule->getRound()->getPlace($rankingSportItem->getPerformance()->getPlace());
            }, $this->getItemsForHorizontalPoule($horizontalPoule, true))
        );
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @param bool|null $checkOnSingleQualifyRule
     * @return list<SportRoundRankingItem>
     */
    public function getItemsForHorizontalPoule(HorizontalPoule $horizontalPoule, bool $checkOnSingleQualifyRule = null): array
    {
        $performances = [];
        foreach ($horizontalPoule->getPlaces() as $place) {
            if ($checkOnSingleQualifyRule === true && $this->hasPlaceSingleQualifyRule($place)) {
                continue;
            }
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
     * @param list<SportPerformance> $originalPerformances
     * @param list<int> $rankingRules
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
     * @param list<SportPerformance> $performances
     * @return list<SportRoundRankingItem>
     */
    protected function getItemsHelper(Round $round, array $performances): array
    {
        $scoreConfig = $round->getValidScoreConfig($this->competitionSport);
        $ruleSet = $this->competitionSport->getCompetition()->getRankingRuleSet();
        $rankingRules = $this->rankingRuleGetter->getRules($ruleSet, $scoreConfig->useSubScore());
        return $this->rankItems($performances, $rankingRules);
    }

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
     * @param list<int> $rankingRules
     * @return list<SportPerformance>
     */
    protected function findBestPerformances(array $originalPerformances, array $rankingRules): array
    {
        $bestPerformances = $originalPerformances;
        foreach ($rankingRules as $rankingRule) {
            $rankingFunction = $this->rankFunctionMap[$rankingRule];
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

    // Place can have a multiple and a single rule, if so than do not process place for horizontalpoule(multiple)
    protected function hasPlaceSingleQualifyRule(Place $place): bool
    {
        return count(array_filter($place->getToQualifyRules(), function (SingleQualifyRule|MultipleQualifyRule $qualifyRuleIt): bool {
            return ($qualifyRuleIt instanceof SingleQualifyRule);
        })) > 0;
    }
}
