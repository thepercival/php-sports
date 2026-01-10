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

    #[\Override]
    public function getCategoryNr(): int
    {
        return $this->categoryNr;
    }

    #[\Override]
    public function getPouleNr(): int
    {
        return $this->pouleNr;
    }

    public function setPouleNr(int $pouleNr): void
    {
        $this->pouleNr = $pouleNr;
    }

    #[\Override]
    public function getPlaceNr(): int
    {
        return $this->placeNr;
    }

    public function setPlaceNr(int $placeNr): void
    {
        $this->placeNr = $placeNr;
    }

    public function equals(StartLocationInterface $startLocation): bool
    {
        return $startLocation->getCategoryNr() === $this->getCategoryNr()
            && $startLocation->getPouleNr() === $this->getPouleNr()
            && $startLocation->getPlaceNr() === $this->getPlaceNr();
    }

    #[\Override]
    public function getStartId(): string
    {
        return $this->getCategoryNr() . '.' . $this->getPouleNr() . '.' . $this->getPlaceNr();
    }
}
