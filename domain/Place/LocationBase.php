<?php

namespace Sports\Place;

class LocationBase implements Location
{
    protected int $pouleNr;
    protected int $placeNr;

    public function __construct(int $pouleNr, int $placeNr)
    {
        $this->pouleNr = $pouleNr;
        $this->placeNr = $placeNr;
    }

    public function getPouleNr(): int
    {
        return $this->pouleNr;
    }

    public function getPlaceNr(): int
    {
        return $this->placeNr;
    }
}
