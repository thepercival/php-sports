<?php
declare(strict_types=1);

namespace Sports\Place;

class Location implements LocationInterface
{
    public function __construct(protected int $pouleNr, protected int $placeNr)
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

    public function getRoundLocationId(): string
    {
        return $this->getPouleNr() . '.' . $this->getPlaceNr();
    }
}
