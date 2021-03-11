<?php

declare(strict_types=1);

namespace Sports\Ranking\Calculator\Round;

use Exception;
use Sports\Game\Against as AgainstGame;
use Sports\Place\Location as PlaceLocation;
use Sports\Poule;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Place;
use Sports\Round;
use Sports\Ranking\RoundItem\Ranked as RankedRoundItem;
use Sports\Ranking\RoundItem\SportUnranked as UnrankedRoundItem;
use Sports\Ranking\ItemsGetter\Against as AgainstItemsGetter;
use Sports\Ranking\Calculator as RankingService;
use Sports\State;
use SportsHelpers\Against\Side as AgainstSide;


class Sport
{
    protected array $map = [];

    public function __construct(Round $round, int $rulesSet, int $gameStates = null)
    {
        $this->initMap();
    }


    /**
     * @param Poule $poule
     * @return array | RankedRoundItem[]
     */
    public function getItemsForPoule(Poule $poule): array
    {
        if (array_key_exists($poule->getNumber(), $this->cache) === false) {
            $round = $poule->getRound();
            $getter = new AgainstItemsGetter($round, $this->gameStates);
            $unrankedItems = $getter->getUnrankedItems($poule->getPlaces()->toArray(), $poule->getGames()->toArray());
            $rankedItems = $this->rankItems($unrankedItems, true);
            $this->cache[$poule->getNumber()] = $rankedItems;
        }
        return $this->cache[$poule->getNumber()];
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @return array | PlaceLocation[]
     */
    public function getPlaceLocationsForHorizontalPoule(HorizontalPoule $horizontalPoule): array
    {
        return array_map(
            function (RankedRoundItem $rankingItem): PlaceLocation {
                return $rankingItem->getPlaceLocation();
            },
            $this->getItemsForHorizontalPoule($horizontalPoule, true)
        );
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @return array | Place[]
     */
    public function getPlacesForHorizontalPoule(HorizontalPoule $horizontalPoule): array
    {
        return array_map(
            function (RankedRoundItem $rankingItem): Place {
                return $rankingItem->getPlace();
            },
            $this->getItemsForHorizontalPoule($horizontalPoule, true)
        );
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @param bool|null $checkOnSingleQualifyRule
     * @return array | RankedRoundItem[]
     */
    public function getItemsForHorizontalPoule(HorizontalPoule $horizontalPoule, ?bool $checkOnSingleQualifyRule): array
    {
        $unrankedRoundItems = [];
        foreach ($horizontalPoule->getPlaces() as $place) {
            if ($checkOnSingleQualifyRule && $this->hasPlaceSingleQualifyRule($place)) {
                continue;
            }
            $pouleRankingItems = $this->getItemsForPoule($place->getPoule());
            $pouleRankingItem = $this->getItemByRank($pouleRankingItems, $place->getNumber());
            $unrankedRoundItems[] = $pouleRankingItem->getUnranked();
        }
        return $this->rankItems($unrankedRoundItems, false);
    }

    /**
     * Place can have a multiple and a single rule, if so than do not process place for horizontalpoule(multiple)
     *
     * @param Place $place
     * @return bool
     */
    protected function hasPlaceSingleQualifyRule(Place $place): bool
    {
        $foundRules = array_filter(
            $place->getToQualifyRules(),
            function ($qualifyRuleIt) {
                return $qualifyRuleIt->isSingle();
            }
        );
        return count($foundRules) > 0;
    }

    /**
     * @param array $rankingItems | RankedRoundItem[]
     * @param int $rank
     * @return RankedRoundItem
     */
    public function getItemByRank(array $rankingItems, int $rank): RankedRoundItem
    {
        $foundItems = array_filter(
            $rankingItems,
            function ($rankingItemIt) use ($rank): bool {
                return $rankingItemIt->getUniqueRank() === $rank;
            }
        );
        return reset($foundItems);
    }

    /**
     * @param array | UnrankedRoundItem[] $unrankedItems
     * @param bool $againstEachOther
     * @return array | RankedRoundItem[]
     */
    private function rankItems(array $unrankedItems, bool $againstEachOther): array
    {
        $rankedItems = [];
        $rankFunctions = $this->getRankFunctions($againstEachOther);
        $nrOfIterations = 0;
        while (count($unrankedItems) > 0) {
            $bestItems = $this->findBestItems($unrankedItems, $rankFunctions);
            uasort( $bestItems, function (UnrankedRoundItem $unrankedA, UnrankedRoundItem $unrankedB): int {
                if ($unrankedA->getPlaceLocation()->getPouleNr() === $unrankedB->getPlaceLocation()->getPouleNr()) {
                    return $unrankedA->getPlaceLocation()->getPlaceNr() - $unrankedB->getPlaceLocation()->getPlaceNr();
                }
                return $unrankedA->getPlaceLocation()->getPouleNr() - $unrankedB->getPlaceLocation()->getPouleNr();
            });
            $rank = $nrOfIterations + 1;
            foreach ($bestItems as $bestItem) {
                array_splice($unrankedItems, array_search($bestItem, $unrankedItems, true), 1);
                $rankedItems[] = new RankedRoundItem($bestItem, ++$nrOfIterations, $rank);
            }
            // if (nrOfIterations > this.maxPlaces) {
            //     console.error('should not be happening for ranking calc');
            //     break;
            // }
        }
        return $rankedItems;
    }

    /**
     * @param array | UnrankedRoundItem[] $orgItems
     * @param array $rankFunctions
     * @return array | UnrankedRoundItem[]
     */
    private function findBestItems(array $orgItems, array $rankFunctions): array
    {
        $bestItems = $orgItems;

        foreach ($rankFunctions as $rankFunction) {
            if ($rankFunction === $this->rankFunctions[RankingService::BestAgainstEachOther] && count($orgItems) === count(
                    $bestItems
                )) {
                continue;
            }
            $bestItems = $rankFunction($bestItems);
            if (count($bestItems) < 2) {
                break;
            }
        }
        return $bestItems;
    }











}
}
