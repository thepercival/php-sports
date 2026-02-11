<?php

declare(strict_types=1);

namespace Sports\Qualify\RoundRank;

use Sports\Category;
use Sports\Round;
use Sports\Qualify\QualifyTarget as QualifyTarget;

final class Calculator
{
    /**
     * @var array<string, int>
     */
    protected array $map;

    public function __construct(Category $category)
    {
        $this->constructMap($category->getRootRound());
    }

    public function getRank(Round $round): int
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
