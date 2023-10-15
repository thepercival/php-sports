<?php

declare(strict_types=1);

namespace Sports\Qualify\Mapping;

use Sports\Place;
use Sports\Poule;
use Sports\Qualify\Mapping;

class ByPlace extends Mapping
{
    public function __construct(private Place $fromPlace, Place $toPlace)
    {
        parent::__construct($toPlace);
    }

    public function getFromPlace(): Place
    {
        return $this->fromPlace;
    }

    public function getFromPoule(): Poule
    {
        return $this->fromPlace->getPoule();
    }
}
