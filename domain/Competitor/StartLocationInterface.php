<?php

declare(strict_types=1);

namespace Sports\Competitor;

interface StartLocationInterface
{
    public function getCategoryNr(): int;

    public function getPouleNr(): int;

    public function getPlaceNr(): int;

    public function getStartId(): string;
}
