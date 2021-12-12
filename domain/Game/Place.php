<?php

declare(strict_types=1);

namespace Sports\Game;

use Sports\Place as PlaceBase;
use SportsHelpers\Identifiable;

abstract class Place extends Identifiable
{
    protected PlaceBase $place;

    public function __construct(PlaceBase $place)
    {
        $this->place = $place;
    }

    public function getPlace(): PlaceBase
    {
        return $this->place;
    }
}
