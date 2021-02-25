<?php

declare(strict_types=1);

namespace Sports\Ranking\Service;

use Exception;
use Sports\Game\Against as AgainstGame;
use Sports\Place\Location as PlaceLocation;
use Sports\Poule;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Place;
use Sports\Round;
use Sports\Ranking\RoundItem\Ranked as RankedRoundItem;
use Sports\Ranking\RoundItem\Unranked as UnrankedRoundItem;
use Sports\Ranking\ItemsGetter\Against as AgainstItemsGetter;
use Sports\Ranking\Service as RankingService;
use Sports\State;
use SportsHelpers\Against\Side as AgainstSide;

/* tslint:disable:no-bitwise */

class Against
{
    private Round $round;
    private int $rulesSet;
    private int $gameStates;
    private array $cache = [];
    /**
     * @var array
     */
    private array $rankFunctions;

    const RULESSET_WC = 1;
    const RULESSET_EC = 2;

    public function __construct(Round $round, int $rulesSet, int $gameStates = null)
    {
        $this->round = $round;
        $this->rulesSet = $rulesSet;
        $this->gameStates = $gameStates !== null ? $gameStates : State::Finished;
        $this->initRankFunctions();
    }

    /**
     * @return array|string[]
     * @throws Exception
     */
    public function getRuleDescriptions(): array
    {
        return array_map(
            function ($rankFunction) {
                if ($rankFunction === $this->rankFunctions[RankingService::MostPoints]) {
                    return 'het meeste aantal punten';
                } elseif ($rankFunction === $this->rankFunctions[RankingService::FewestGames]) {
                    return 'het minste aantal wedstrijden';
                } elseif ($rankFunction === $this->rankFunctions[RankingService::BestUnitDifference]) {
                    return 'het beste saldo';
                } elseif ($rankFunction === $this->rankFunctions[RankingService::MostUnitsScored]) {
                    return 'het meeste aantal eenheden voor';
                } else /* if ($rankFunction === $this->rankFunctions[Service::BestAgainstEachOther]) */ {
                    return 'het beste onderling resultaat';
                }
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

    /**
     * @param bool|null $againstEachOther
     * @return array
     */
    private function getRankFunctions(bool $againstEachOther = null): array
    {
        $rankFunctions = [
            $this->rankFunctions[RankingService::MostPoints],
            $this->rankFunctions[RankingService::FewestGames]
        ];
        $unitRankFunctions = [
            $this->rankFunctions[RankingService::BestUnitDifference],
            $this->rankFunctions[RankingService::MostUnitsScored],
            $this->rankFunctions[RankingService::BestSubUnitDifference],
            $this->rankFunctions[RankingService::MostSubUnitsScored]
        ];
        if ($this->rulesSet === self::RULESSET_WC) {
            $rankFunctions = array_merge($rankFunctions, $unitRankFunctions);
            if ($againstEachOther !== false) {
                $rankFunctions[] = $this->rankFunctions[RankingService::BestAgainstEachOther];
            }
        } elseif ($this->rulesSet === self::RULESSET_EC) {
            if ($againstEachOther !== false) {
                $rankFunctions[] = $this->rankFunctions[RankingService::BestAgainstEachOther];
            }
            $rankFunctions = array_merge($rankFunctions, $unitRankFunctions);
        } else {
            throw new Exception('Unknown qualifying rule', E_ERROR);
        }

        return $rankFunctions;
    }

    protected function initRankFunctions()
    {
        $this->rankFunctions = array();

        $this->rankFunctions[RankingService::MostPoints] = function (array $items): array {
            $mostPoints = null;
            $bestItems = [];
            foreach ($items as $item) {
                $points = $item->getPoints();
                if ($mostPoints === null || $points === $mostPoints) {
                    $mostPoints = $points;
                    $bestItems[] = $item;
                } elseif ($points > $mostPoints) {
                    $mostPoints = $points;
                    $bestItems = [];
                    $bestItems[] = $item;
                }
            }
            return $bestItems;
        };

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

        $getGamesBetweenEachOther = function (array $places, array $games): array {
            $gamesRet = [];
            foreach ($games as $p_gameIt) {
                if (($p_gameIt->getState() & $this->gameStates) === 0) {
                    continue;
                }
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
        };

        $this->rankFunctions[RankingService::BestAgainstEachOther] = function (array $items) use ($getGamesBetweenEachOther
        ) : array {
            $places = array_map(
                function ($item) {
                    return $item->getRound()->getPlace($item->getPlaceLocation());
                },
                $items
            );
            $poule = $places[0]->getPoule();
            $round = $poule->getRound();
            $games = $getGamesBetweenEachOther($places, $poule->getGames()->toArray());
            if (count($games) === 0) {
                return $items;
            }
            $getter = new AgainstItemsGetter($round, $this->gameStates);
            $unrankedItems = $getter->getUnrankedItems($places, $games);
            $rankedItems = array_filter(
                $this->rankItems($unrankedItems, true),
                function ($rankItem): bool {
                    return $rankItem->getRank() === 1;
                }
            );
            if (count($rankedItems) === count($items)) {
                return $items;
            }
            return array_map(
                function ($rankedItem) use ($items) {
                    $foundItems = array_filter(
                        $items,
                        function ($item) use ($rankedItem): bool {
                            return $item->getPlaceLocation()->getPouleNr() === $rankedItem->getPlaceLocation(
                                )->getPouleNr()
                                && $item->getPlaceLocation()->getPlaceNr() === $rankedItem->getPlaceLocation(
                                )->getPlaceNr();
                        }
                    );
                    return reset($foundItems);
                },
                $rankedItems
            );
        };

        $bestDifference = function (array $items, bool $sub): array {
            $bestDiff = null;
            $bestItems = [];
            foreach ($items as $item) {
                $diff = $sub ? $item->getSubDiff() : $item->getDiff();
                if ($bestDiff === null || $diff === $bestDiff) {
                    $bestDiff = $diff;
                    $bestItems[] = $item;
                } elseif ($diff > $bestDiff) {
                    $bestDiff = $diff;
                    $bestItems = [$item];
                }
            }
            return $bestItems;
        };

        $this->rankFunctions[RankingService::BestUnitDifference] = function (array $items) use ($bestDifference) : array {
            return $bestDifference($items, false);
        };

        $this->rankFunctions[RankingService::BestSubUnitDifference] = function (array $items) use ($bestDifference): array {
            return $bestDifference($items, true);
        };

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
