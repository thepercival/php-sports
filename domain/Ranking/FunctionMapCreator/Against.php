<?php

declare(strict_types=1);

namespace Sports\Ranking\FunctionMapCreator;

use Sports\Competition\CompetitionSport as CompetitionSport;
use Sports\Game\State as GameState;
use Sports\Place;
use Sports\Place\SportPerformance;
use Sports\Ranking\Calculator\Round\Sport\Against as AgainstSportRoundRankingCalculator;
use Sports\Ranking\FunctionMapCreator as BaseFunctionMapCreator;
use Sports\Ranking\Item\Round\Sport as SportRoundRankingItem;
use Sports\Ranking\Rule;

final class Against extends BaseFunctionMapCreator
{
    /**
     * @param CompetitionSport $competitionSport
     * @param list<GameState> $gameStates
     */
    public function __construct(private CompetitionSport $competitionSport, private array $gameStates)
    {
        parent::__construct();
        $this->initMap();
    }

    private function initMap(): void
    {
        $bestDifference = function (array $sportPerformances, bool $sub): array {
            /** @var list<SportPerformance> $sportPerformances */
            $bestDiff = null;
            $bestSportPerformances = [];
            foreach ($sportPerformances as $sportPerformance) {
                $diff = $sub ? $sportPerformance->getSubDiff() : $sportPerformance->getDiff();
                if ($bestDiff === null || $diff === $bestDiff) {
                    $bestDiff = $diff;
                    $bestSportPerformances[] = $sportPerformance;
                } elseif ($diff > $bestDiff) {
                    $bestDiff = $diff;
                    $bestSportPerformances = [$sportPerformance];
                }
            }
            return $bestSportPerformances;
        };
        $this->map[Rule::BestUnitDifference->name] = function (array $sportPerformances) use ($bestDifference): array {
            /** @var list<SportPerformance> $sportPerformances */
            return $bestDifference($sportPerformances, false);
        };
        $this->map[Rule::BestSubUnitDifference->name] = function (array $sportPerformances) use ($bestDifference): array {
            /** @var list<SportPerformance> $sportPerformances */
            return $bestDifference($sportPerformances, true);
        };
        $this->map[Rule::BestAmongEachOther->name] = function (array $sportPerformances): array {
            /** @var list<SportPerformance> $sportPerformances */
            $places = array_map(
                function (SportPerformance $sportPerformance): Place {
                    return $sportPerformance->getPlace();
                },
                $sportPerformances
            );
            $firstPlace = reset($places);
            if ($firstPlace === false) {
                return [];
            }
            $poule = $firstPlace->getPoule();
            $rankingCalculator = new AgainstSportRoundRankingCalculator($this->competitionSport, $this->gameStates);
            $rankingItems = $rankingCalculator->getItemsAmongPlaces($poule, $places);
            $rankingItems = array_filter($rankingItems, function (SportRoundRankingItem $rankingItem): bool {
                return $rankingItem->getRank() === 1;
            });
            if (count($rankingItems) === count($sportPerformances)) {
                return $sportPerformances;
            }
            $performanceMap = $this->getPerformanceMap($sportPerformances);
            return array_values(array_map(
                function (SportRoundRankingItem $rankingItem) use ($performanceMap): SportPerformance {
                    return $performanceMap[$rankingItem->getPlaceLocation()->getUniqueIndex()];
                },
                $rankingItems
            ));
        };
    }

    /**
     * @param array<SportPerformance> $performances
     * @return array<SportPerformance>
     */
    private function getPerformanceMap(array $performances): array
    {
        $map = [];
        foreach ($performances as $performance) {
            $map[$performance->getPlaceLocation()->getUniqueIndex()] = $performance;
        }
        return $map;
    }
}
