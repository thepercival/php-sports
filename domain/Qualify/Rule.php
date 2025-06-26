<?php

declare(strict_types=1);

namespace Sports\Qualify;

use Sports\Round;
use Sports\Poule\Horizontal as HorizontalPoule;

abstract class Rule
{
    public function __construct(protected HorizontalPoule $fromHorizontalPoule)
    {
    }

    public function getQualifyTarget(): QualifyTarget
    {
        return $this->fromHorizontalPoule->getQualifyTarget();
    }

    public function getFromHorizontalPoule(): HorizontalPoule
    {
        return $this->fromHorizontalPoule;
    }

    public function getRank(): int
    {
        return $this->getFromHorizontalPoule()->getAbsoluteNumber();
    }

    public function getFromRound(): Round
    {
        return $this->fromHorizontalPoule->getRound();
    }
}
