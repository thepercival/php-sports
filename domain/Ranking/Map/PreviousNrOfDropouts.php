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
        return $this->map[$round->getStructurePathNode()->pathToString()];
    }

    private function constructMap(Round $startRound): void
    {
        $this->map = [];

        $nrOfDropoutPlaces = 0;
        $setDropouts = function (Round $round) use (&$setDropouts, &$nrOfDropoutPlaces): void {
            foreach ($round->getTargetQualifyGroups(QualifyTarget::WINNERS) as $qualifyGroup) {
                $setDropouts($qualifyGroup->getChildRound());
            }
            $this->map[$round->getStructurePathNode()->pathToString()] = $nrOfDropoutPlaces;
            $nrOfDropoutPlaces += $round->getNrOfDropoutPlaces();
            $losers = $round->getTargetQualifyGroups(QualifyTarget::LOSERS)->toArray();

            foreach (array_reverse($losers) as $losersQualifyGroup) {
                $setDropouts($losersQualifyGroup->getChildRound());
            }
        };
        $setDropouts($startRound);
    }
}
