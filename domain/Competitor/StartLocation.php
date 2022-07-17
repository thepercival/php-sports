<?php

declare(strict_types=1);

namespace Sports\Competitor;

use InvalidArgumentException;
use SportsHelpers\Identifiable;

class StartLocation implements StartLocationInterface
{
    public function __construct(protected int $categoryNr, protected int $pouleNr, protected int $placeNr)
    {
    }

    public function getCategoryNr(): int
    {
        return $this->categoryNr;
    }

    public function getPouleNr(): int
    {
        return $this->pouleNr;
    }

    public function setPouleNr(int $pouleNr): void
    {
        $this->pouleNr = $pouleNr;
    }

    public function getPlaceNr(): int
    {
        return $this->placeNr;
    }

    public function setPlaceNr(int $placeNr): void
    {
        $this->placeNr = $placeNr;
    }

    public function getStartId(): string
    {
        return $this->getCategoryNr() . '.' . $this->getPouleNr() . '.' . $this->getPlaceNr();
    }
}
