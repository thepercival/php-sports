<?php

declare(strict_types=1);

namespace Sports\Competitor;

use InvalidArgumentException;
use SportsHelpers\Identifiable;

class Base extends Identifiable
{
    protected int $max_length_info = 200;

    protected bool $registered = false;
    protected string|null $info = null;

    public function __construct(protected int $pouleNr, protected int $placeNr)
    {
    }

    public function getRegistered(): bool
    {
        return $this->registered;
    }

    public function setRegistered(bool $registered): void
    {
        $this->registered = $registered;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function setInfo(string $info = null): void
    {
        if ($info !== null && strlen($info) === 0) {
            $info = null;
        }
        if ($info !== null && strlen($info) > $this->max_length_info) {
            throw new InvalidArgumentException("de extra-info mag maximaal ".$this->max_length_info." karakters bevatten", E_ERROR);
        }
        $this->info = $info;
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

    public function getRoundLocationId(): string
    {
        return $this->getPouleNr() . '.' . $this->getPlaceNr();
    }
}
