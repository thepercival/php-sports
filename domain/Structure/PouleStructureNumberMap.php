<?php

declare(strict_types=1);

namespace Sports\Structure;

use Closure;
use Sports\Qualify\RoundRank\Service as RoundRankService;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Poule;

final class PouleStructureNumberMap
{
    /**
     * @var array<string, int>
     */
    protected array $map;

    public function __construct(RoundNumber $roundNumber, RoundRankService $roundRankService)
    {
        $this->constructMap($roundNumber, $roundRankService);
    }

    public function get(Poule $poule): int
    {
        return $this->map[(string)$poule->getStructureLocation()];
    }

    private function constructMap(RoundNumber $startRoundNumber, RoundRankService $roundRankService): void
    {
        $this->map = [];

        $pouleNr = 1;
        $setPouleStructureNumbers = function (RoundNumber $roundNumber) use (
            $roundRankService,
            &$pouleNr,
            &$setPouleStructureNumbers
        ): void {
            /** @var Closure $setPouleStructureNumbers */
            $rounds = $roundNumber->getRounds();
            uasort($rounds, function (Round $roundA, Round $roundB) use ($roundRankService): int {
                if ($roundRankService->getRank($roundA) === $roundRankService->getRank($roundB)) {
                    return $roundRankService->getRank($roundA) > $roundRankService->getRank($roundB) ? 1 : -1;
                }
                return $roundA->getCategory()->getNumber() > $roundB->getCategory()->getNumber() ? 1 : -1;
            });
            foreach ($rounds as $round) {
                foreach ($round->getPoules() as $poule) {
                    /** @var int $pouleNr */
                    $this->map[(string)$poule->getStructureLocation()] = $pouleNr;
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
