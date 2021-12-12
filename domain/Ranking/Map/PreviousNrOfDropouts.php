<?php

declare(strict_types=1);

namespace Sports\Ranking\Map;

use Sports\Round;
use Sports\Qualify\Target as QualifyTarget;

class PreviousNrOfDropouts
{
    /**
     * @var array<string, int>
     */
    protected array $map;

    public function __construct(Round $rootRound)
    {
        $this->constructMap($rootRound->getRoot());
    }

    public function get(Round $round): int
    {
        return $this->map[(string)$round->getStructurePathNode()];
    }

    private function constructMap(Round $startRound): void
    {
        $this->map = [];

        $nrOfDropoutPlaces = 0;
        $setDropouts = function (Round $round) use (&$setDropouts, &$nrOfDropoutPlaces): void {
            foreach ($round->getTargetQualifyGroups(QualifyTarget::Winners) as $qualifyGroup) {
                /** @var \Closure $setDropouts */
                $setDropouts($qualifyGroup->getChildRound());
            }
            /** @var int $nrOfDropoutPlaces */
            $this->map[(string)$round->getStructurePathNode()] = $nrOfDropoutPlaces;
            $nrOfDropoutPlaces += $round->getNrOfDropoutPlaces();
            $losers = $round->getTargetQualifyGroups(QualifyTarget::Losers)->toArray();

            foreach (array_reverse($losers) as $losersQualifyGroup) {
                /** @var \Closure $setDropouts */
                $setDropouts($losersQualifyGroup->getChildRound());
            }
        };
        $setDropouts($startRound);
    }
}
