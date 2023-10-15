<?php

declare(strict_types=1);

namespace Sports\Qualify;

use Sports\Place;

class Mapping
{
    public function __construct(private Place $toPlace)
    {
    }

    public function getToPlace(): Place
    {
        return $this->toPlace;
    }
}
