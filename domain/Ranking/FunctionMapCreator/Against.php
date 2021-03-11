<?php
declare(strict_types=1);

namespace Sports\Ranking\FunctionMapCreator;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Ranking\Rule;
use Sports\Ranking\FunctionMapCreator as BaseFunctionMapCreator;
use Sports\Ranking\Calculator\Round\Sport\Against as AgainstSportRoundRankingCalculator;
use Sports\Ranking\Item\Round\SportRanked as RankedSportRoundItem;

class Against extends BaseFunctionMapCreator
{
    /**
     * Against constructor.
     * @param CompetitionSport $competitionSport
     * @param array|int[] $gameStates
     */
    public function __construct(private CompetitionSport $competitionSport, private array $gameStates)
    {
        parent::__construct();
        $this->initMap();
    }

    private function initMap()
    {
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
        $this->map[Rule::BestUnitDifference] = function (array $items) use ($bestDifference) : array {
            return $bestDifference($items, false);
        };
        $this->map[Rule::BestSubUnitDifference] = function (array $items) use ($bestDifference): array {
            return $bestDifference($items, true);
        };

        /*$getGamesBetweenEachOther = function (array $places, array $games): array {
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
        };*/

        $this->map[Rule::BestAmongEachOther] = function (array $unrankedItems) : array {
            $places = array_map(
                function ($item) {
                    return $item->getRound()->getPlace($item->getPlaceLocation());
                },
                $unrankedItems
            );
            $poule = $places[0]->getPoule();
            $rankingCalculator = new AgainstSportRoundRankingCalculator($this->competitionSport, $this->gameStates);
            $rankedItems = $rankingCalculator->getItemsAmongPlaces($poule, $places);
            $rankedItems = array_filter($rankedItems, function (RankedSportRoundItem $rankedItem): bool {
                return $rankedItem->getRank() === 1;
            });
            if (count($rankedItems) === count($unrankedItems)) {
                return $unrankedItems;
            }
            return array_map(
                function ($rankedItem) use ($unrankedItems) {
                    $foundItems = array_filter(
                        $unrankedItems,
                        function ($unrankedItem) use ($rankedItem): bool {
                            return $unrankedItem->getPlaceLocation()->getPouleNr() === $rankedItem->getPlaceLocation(
                                )->getPouleNr()
                                && $unrankedItem->getPlaceLocation()->getPlaceNr() === $rankedItem->getPlaceLocation(
                                )->getPlaceNr();
                        }
                    );
                    return reset($foundItems);
                },
                $rankedItems
            );
        };
    }
}
