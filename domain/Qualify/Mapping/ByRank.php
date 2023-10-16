<?php

declare(strict_types=1);

namespace Sports\Qualify\Mapping;

use Sports\Place;
use Sports\Qualify\Mapping;

class ByRank extends Mapping
{
    public function __construct(private readonly int $fromRank, Place $toPlace)
    {
        parent::__construct($toPlace);
    }

//    public function getFromPoule(): Poule
//    {
//        return $this->fromPoule;
//    }

    public function getFromRank(): int
    {
        return $this->fromRank;
    }
}
