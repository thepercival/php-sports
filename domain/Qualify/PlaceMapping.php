<?php

declare(strict_types=1);

namespace Sports\Qualify;

use Sports\Place;
use Sports\Poule;

class PlaceMapping
{
    public function __construct(private Place $fromPlace, private Place $toPlace)
    {
    }

    public function getFromPlace(): Place
    {
        return $this->fromPlace;
    }

    public function getFromPoule(): Poule
    {
        return $this->fromPlace->getPoule();
    }

    public function getToPlace(): Place
    {
        return $this->toPlace;
    }
}
