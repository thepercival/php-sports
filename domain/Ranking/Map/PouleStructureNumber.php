<?php
declare(strict_types=1);

namespace Sports\Ranking\Map;

use Sports\Ranking\Map\PreviousNrOfDropouts as PreviousNrOfDropoutsMap;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Poule;

class PouleStructureNumber
{
    /**
     * @var array<string, int>
     */
    protected array $map;

    public function __construct(RoundNumber $roundNumber, private PreviousNrOfDropoutsMap $previousNrOfDropoutsMap)
    {
        $this->constructMap($roundNumber);
    }

    public function get(Poule $poule): int
    {
        return $this->map[$poule->getStructureLocation()];
    }

    private function constructMap(RoundNumber $startRoundNumber): void
    {
        $this->map = [];

        $pouleNr = 1;
        $setPouleStructureNumbers = function (RoundNumber $roundNumber) use (&$pouleNr, &$setPouleStructureNumbers) : void {
            /** @var \Closure $setPouleStructureNumbers */
            $rounds = $roundNumber->getRounds()->toArray();
            uasort($rounds, function (Round $roundA, Round $roundB): int {
                return $this->previousNrOfDropoutsMap->get($roundA) > $this->previousNrOfDropoutsMap->get($roundB) ? 1 : -1;
            });
            foreach ($rounds as $round) {
                foreach ($round->getPoules() as $poule) {
                    /** @var int $pouleNr */
                    $this->map[$poule->getStructureLocation()] = $pouleNr;
                    $pouleNr++;
                }
            }
            $nextRoundNumber = $roundNumber->getNext();
            if ($nextRoundNumber !== null) {
                $setPouleStructureNumbers($nextRoundNumber);
            }
        };
        $setPouleStructureNumbers($startRoundNumber);
    }
}
