<?php

namespace Sports\Qualify\Rule;

use Doctrine\Common\Collections\Collection;
use Sports\Place;
use Sports\Qualify\PlaceMapping as QualifyPlaceMapping;

interface Single
{
    /**
     * @return Collection<int, QualifyPlaceMapping>
     */
    public function getMappings(): Collection;
    public function getNrOfToPlaces(): int;
}