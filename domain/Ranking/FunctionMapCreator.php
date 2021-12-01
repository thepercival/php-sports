<?php
declare(strict_types=1);

namespace Sports\Ranking;

use Closure;
use Sports\Place\SportPerformance;
use Sports\Ranking\Rule as RankingRule;

class FunctionMapCreator
{
    /**
     * @var array<string, Closure(list<SportPerformance>):list<SportPerformance>>
     */
    protected array $map = [];

    public function __construct()
    {
        $this->initMap();
    }

    /**
     * @return array<string, Closure(list<SportPerformance>):list<SportPerformance>>
     */
    public function getMap(): array
    {
        return $this->map;
    }

    private function initMap(): void
    {
        $this->map[Rule::MostPoints->name] = function (array $sportPerformances): array {
            /** @var list<SportPerformance> $sportPerformances */
            $mostPoints = null;
            $bestSportPerformances = [];
            foreach ($sportPerformances as $sportPerformance) {
                $points = $sportPerformance->getPoints();
                if ($mostPoints === null || $points === $mostPoints) {
                    $mostPoints = $points;
                    $bestSportPerformances[] = $sportPerformance;
                } elseif ($points > $mostPoints) {
                    $mostPoints = $points;
                    $bestSportPerformances = [];
                    $bestSportPerformances[] = $sportPerformance;
                }
            }
            return $bestSportPerformances;
        };
        $this->map[Rule::FewestGames->name] = function (array $sportPerformances): array {
            /** @var list<SportPerformance> $sportPerformances */
            $fewestGames = null;
            $bestSportPerformances = [];
            foreach ($sportPerformances as $sportPerformance) {
                $nrOfGames = $sportPerformance->getGames();
                if ($fewestGames === null || $nrOfGames === $fewestGames) {
                    $fewestGames = $nrOfGames;
                    $bestSportPerformances[] = $sportPerformance;
                } elseif ($nrOfGames < $fewestGames) {
                    $fewestGames = $nrOfGames;
                    $bestSportPerformances = [$sportPerformance];
                }
            }
            return $bestSportPerformances;
        };
        $mostScored = function (array $sportPerformances, bool $sub): array {
            /** @var list<SportPerformance> $sportPerformances */
            $mostScored = null;
            $bestSportPerformances = [];
            foreach ($sportPerformances as $sportPerformance) {
                $scored = $sub ? $sportPerformance->getSubScored() : $sportPerformance->getScored();
                if ($mostScored === null || $scored === $mostScored) {
                    $mostScored = $scored;
                    $bestSportPerformances[] = $sportPerformance;
                } elseif ($scored > $mostScored) {
                    $mostScored = $scored;
                    $bestSportPerformances = [$sportPerformance];
                }
            }
            return $bestSportPerformances;
        };
        $this->map[Rule::MostUnitsScored->name] = function (array $sportPerformances) use ($mostScored): array {
            /** @var list<SportPerformance> $sportPerformances */
            return $mostScored($sportPerformances, false);
        };
        $this->map[Rule::MostSubUnitsScored->name] = function (array $sportPerformances) use ($mostScored): array {
            /** @var list<SportPerformance> $sportPerformances */
            return $mostScored($sportPerformances, true);
        };
    }
}
