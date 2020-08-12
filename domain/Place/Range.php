<?php

namespace Sports\Place;

use SportsHelpers\Range as BaseRange;

class Range extends BaseRange
{
    /**
     * @var BaseRange
     */
    private $placesPerPouleRange;

    public function __construct(int $min, int $max, BaseRange $placesPerPouleRange)
    {
        parent::__construct($min, $max);
        $this->placesPerPouleRange = $placesPerPouleRange;
    }

    public function getPlacesPerPouleRange(): BaseRange
    {
        return $this->placesPerPouleRange;
    }
}
