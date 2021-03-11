<?php

namespace Sports\Ranking\Item\End;

use Sports\Place\Location as PlaceLocation;

class End
{
    /**
     * @var PlaceLocation|null
     */
    private $placeLocation;

    public function __construct(private int $uniqueRank, private int $rank, PlaceLocation $placeLocation = null)
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

    public function getPlaceLocation(): ?PlaceLocation
    {
        return $this->placeLocation;
    }
}