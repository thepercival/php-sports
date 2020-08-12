<?php

namespace Sports\Ranking\End;

use Sports\Place\Location as PlaceLocation;

class Item
{
    /**
     * @var int
     */
    private $uniqueRank;
    /**
     * @var int
     */
    private $rank;
    /**
     * @var PlaceLocation|null
     */
    private $placeLocation;

    public function __construct(int $uniqueRank, int $rank, PlaceLocation $placeLocation = null)
    {
        $this->uniqueRank = $uniqueRank;
        $this->rank = $rank;
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