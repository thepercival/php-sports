<?php

declare(strict_types=1);

namespace Sports\Ranking\Service;

use Sports\Place\Location as PlaceLocation;
use Sports\Poule;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Place;
use Sports\Round;
use Sports\Ranking\RoundItem\Ranked as RankedRoundItem;
use Sports\Ranking\RoundItem\Unranked as UnrankedRoundItem;
use Sports\State;
use Sports\Ranking\Service as RankingService;
use Sports\Ranking\ItemsGetter\Together as TogetherItemsGetter;

/* tslint:disable:no-bitwise */

class Together
{
    private Round $round;
    private int $rulesSet;
    private int $gameStates;
    private array $cache = [];
    /**
     * @var array
     */
    private array $rankFunctions;

    public function __construct(Round $round, int $rulesSet, int $gameStates = null)
    {
        $this->round = $round;
        $this->rulesSet = $rulesSet;
        $this->gameStates = $gameStates !== null ? $gameStates : State::Finished;
        $this->initRankFunctions();
    }

    /**
     * @return array|string[]
     */
    public function getRuleDescriptions(): array
    {
        return array_map(
            function ($rankFunction) {
                if ($rankFunction === $this->rankFunctions[RankingService::MostPoints]) {
                    return 'het meeste aantal punten';
                } /*elseif ($rankFunction === $this->rankFunctions[RankingService::FewestGames]) {*/
                return 'het minste aantal wedstrijden';
                //}
            },
            array_filter(
                $this->getRankFunctions(),
                function ($rankFunction): bool {
                    return $rankFunction !== $this->rankFunctions[RankingService::BestSubUnitDifference]
                        && $rankFunction !== $this->rankFunctions[RankingService::MostSubUnitsScored];
                }
            )
        );
    }

    /**
     * @param Poule $poule
     * @return array | RankedRoundItem[]
     */
    public function getItemsForPoule(Poule $poule): array
    {
        if (array_key_exists($poule->getNumber(), $this->cache) === false) {
            $round = $poule->getRound();
            $getter = new TogetherItemsGetter($round, $this->gameStates);
            $unrankedItems = $getter->getUnrankedItems($poule->getPlaces()->toArray(), $poule->getGames()->toArray());
            $this->cache[$poule->getNumber()] = $this->rankItems($unrankedItems);
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
        return $this->rankItems($unrankedRoundItems);
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
     * @return array | RankedRoundItem[]
     */
    private function rankItems(array $unrankedItems): array
    {
        $rankedItems = [];
        $rankFunctions = $this->getRankFunctions();
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
            $bestItems = $rankFunction($bestItems);
            if (count($bestItems) < 2) {
                break;
            }
        }
        return $bestItems;
    }

    /**
     * @return array
     */
    private function getRankFunctions(): array
    {
        return [
            $this->rankFunctions[RankingService::MostUnitsScored],
            $this->rankFunctions[RankingService::MostSubUnitsScored],
            $this->rankFunctions[RankingService::FewestGames]
        ];
    }

    protected function initRankFunctions()
    {
        $this->rankFunctions = array();

        $this->rankFunctions[RankingService::FewestGames] = function (array $items): array {
            $fewestGames = null;
            $bestItems = [];
            foreach ($items as $item) {
                $nrOfGames = $item->getGames();
                if ($fewestGames === null || $nrOfGames === $fewestGames) {
                    $fewestGames = $nrOfGames;
                    $bestItems[] = $item;
                } elseif ($nrOfGames < $fewestGames) {
                    $fewestGames = $nrOfGames;
                    $bestItems = [$item];
                }
            }
            return $bestItems;
        };
        /**
         * @param array|UnrankedRoundItem[] $items
         * @param bool $sub
         * @return array
         */
        $mostScored = function (array $items, bool $sub): array {
            $mostScored = null;
            $bestItems = [];
            foreach ($items as $item) {
                $scored = $sub ? $item->getSubScored() : $item->getScored();
                if ($mostScored === null || $scored === $mostScored) {
                    $mostScored = $scored;
                    $bestItems[] = $item;
                } elseif ($scored > $mostScored) {
                    $mostScored = $scored;
                    $bestItems = [$item];
                }
            }
            return $bestItems;
        };

        $this->rankFunctions[RankingService::MostUnitsScored] = function (array $items) use ($mostScored): array {
            return $mostScored($items, false);
        };

        $this->rankFunctions[RankingService::MostSubUnitsScored] = function (array $items) use ($mostScored): array {
            return $mostScored($items, true);
        };
    }
}
