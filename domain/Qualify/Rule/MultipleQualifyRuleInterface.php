<?php

namespace Sports\Qualify\Rule;

use Sports\Place;

interface MultipleQualifyRuleInterface
{
    /**
     * @return list<Place>
     */
    public function getToPlaces(): array;
}