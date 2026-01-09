<?php

namespace Sports\Place;

use SportsHelpers\PlaceLocationInterface;

final class Location implements PlaceLocationInterface
{
    public function __construct(public int $pouleNr, public int $placeNr)
    {
    }

    #[\Override]
    public function getPouleNr(): int
    {
        return $this->pouleNr;
    }

    #[\Override]
    public function getPlaceNr(): int
    {
        return $this->placeNr;
    }

    #[\Override]
    public function getUniqueIndex(): string
    {
        return $this->getPouleNr() . '.' . $this->getPlaceNr();
    }
}