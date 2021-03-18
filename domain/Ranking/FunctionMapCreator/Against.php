<?php
declare(strict_types=1);

namespace Sports\Ranking\FunctionMapCreator;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Ranking\Rule;
use Sports\Ranking\FunctionMapCreator as BaseFunctionMapCreator;
use Sports\Ranking\Calculator\Round\Sport\Against as AgainstSportRoundRankingCalculator;
use Sports\Ranking\Item\Round\Sport as SportRoundRankingItem;
use Sports\Place\SportPerformance;
use Sports\Place;

class Against extends BaseFunctionMapCreator
{
    /**
     * @param CompetitionSport $competitionSport
     * @param array|int[] $gameStates
     */
    public function __construct(private CompetitionSport $competitionSport, private array $gameStates)
    {
        parent::__construct();
        $this->initMap();
    }

    private function initMap(): void
    {
        /**
         * @param array<SportPerformance> $sportPerformances
         * @return array<SportPerformance>
         */
        $bestDifference = function (array $sportPerformances, bool $sub): array {
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
        /**
         * @param array<SportPerformance> $sportPerformances
         * @return array<SportPerformance>
         */
        $this->map[Rule::BestUnitDifference] = function (array $sportPerformances) use ($bestDifference) : array {
            return $bestDifference($sportPerformances, false);
        };
        /**
         * @param array<SportPerformance> $sportPerformances
         * @return array<SportPerformance>
         */
        $this->map[Rule::BestSubUnitDifference] = function (array $sportPerformances) use ($bestDifference): array {
            return $bestDifference($sportPerformances, true);
        };
        /**
         * @param array<SportPerformance> $sportPerformances
         * @return array<SportPerformance>
         */
        $this->map[Rule::BestAmongEachOther] = function (array $sportPerformances) : array {
            $places = array_map(
                function (SportPerformance $sportPerformance): Place {
                    return $sportPerformance->getPlace();
                },
                $sportPerformances
            );
            $poule = $places[0]->getPoule();
            $rankingCalculator = new AgainstSportRoundRankingCalculator($this->competitionSport, $this->gameStates);
            $rankingItems = $rankingCalculator->getItemsAmongPlaces($poule, $places);
            $rankingItems = array_filter($rankingItems, function (SportRoundRankingItem $rankingItem): bool {
                return $rankingItem->getRank() === 1;
            });
            if (count($rankingItems) === count($sportPerformances)) {
                return $sportPerformances;
            }
            $performanceMap = $this->getPerformanceMap($sportPerformances);
            return array_map(
                function (SportRoundRankingItem $rankingItem) use ($performanceMap): SportPerformance {
                    return $performanceMap[$rankingItem->getRoundLocationId()];
                },
                $rankingItems
            );
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
            $map[$performance->getRoundLocationId()] = $performance;
        }
        return $map;
    }
}
