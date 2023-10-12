<?php

namespace Sports\Qualify\Rule;

use Sports\Place;

interface Multiple
{
    /**
     * @return list<Place>
     */
    public function getToPlaces(): array;
}