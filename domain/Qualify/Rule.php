<?php

namespace Sports\Qualify;

use Sports\Round;
use Sports\Poule\Horizontal as HorizontalPoule;

abstract class Rule
{
    public function __construct(protected HorizontalPoule $fromHorizontalPoule)
    {
    }

    public function getQualifyTarget(): string
    {
        return $this->fromHorizontalPoule->getQualifyTarget();
    }

    public function getFromHorizontalPoule(): HorizontalPoule
    {
        return $this->fromHorizontalPoule;
    }

    public function getNumber(): int
    {
        return $this->getFromHorizontalPoule()->getNumber();
    }

    public function getFromRound(): Round
    {
        return $this->fromHorizontalPoule->getRound();
    }

    public function getFromPlaceNumber(): int
    {
        return $this->getFromHorizontalPoule()->getPlaceNumber();
    }
}
