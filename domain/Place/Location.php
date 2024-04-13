<?php

namespace Sports\Place;

use SportsHelpers\PlaceLocationInterface;

class Location implements PlaceLocationInterface
{
    public function __construct(public int $pouleNr, public int $placeNr)
    {
    }

    public function getPouleNr(): int
    {
        return $this->pouleNr;
    }

    public function getPlaceNr(): int
    {
        return $this->placeNr;
    }

    public function getUniqueIndex(): string
    {
        return $this->getPouleNr() . '.' . $this->getPlaceNr();
    }
}