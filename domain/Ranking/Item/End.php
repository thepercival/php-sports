<?php

namespace Sports\Ranking\Item;

use Sports\Competitor\StartLocationInterface;
use Sports\Place\Location as PlaceLocation;

class End
{
    public function __construct(
        private int $uniqueRank,
        private int $rank,
        private StartLocationInterface|null $startLocation = null
    ) {
    }

    public function getUniqueRank(): int
    {
        return $this->uniqueRank;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function getStartLocation(): StartLocationInterface|null
    {
        return $this->startLocation;
    }
}
