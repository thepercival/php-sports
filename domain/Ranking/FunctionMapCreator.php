<?php
declare(strict_types=1);

namespace Sports\Ranking;

class FunctionMapCreator
{
    /**
     * @var array
     */
    protected $map = [];

    public function __construct()
    {
        $this->initMap();
    }

    public function getMap(): array {
        return $this->map;
    }

    private function initMap()
    {
        $this->map[Rule::MostPoints] = function (array $items): array {
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
        $this->map[Rule::FewestGames] = function (array $items): array {
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
        $this->map[Rule::MostUnitsScored] = function (array $items) use ($mostScored): array {
            return $mostScored($items, false);
        };
        $this->map[Rule::MostSubUnitsScored] = function (array $items) use ($mostScored): array {
            return $mostScored($items, true);
        };
    }
}
