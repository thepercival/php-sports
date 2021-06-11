<?php
declare(strict_types=1);

namespace Sports\Place;

interface LocationInterface
{
    public function getPouleNr(): int;
    public function getPlaceNr(): int;
    public function getRoundLocationId(): string;
}
