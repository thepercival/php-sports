<?php

namespace Sports\Ranking\Item;

use Sports\Place\Location as PlaceLocation;

class End
{
    public function __construct(private int $uniqueRank, private int $rank, private PlaceLocation|null $placeLocation = null)
    {
        $this->placeLocation = $placeLocation;
    }

    public function getUniqueRank(): int
    {
        return $this->uniqueRank;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function getPlaceLocation(): PlaceLocation|null
    {
        return $this->placeLocation;
    }
}
