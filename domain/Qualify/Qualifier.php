<?php

declare(strict_types=1);

namespace Sports\Qualify;

use Sports\Place;
use Sports\Competitor as Competitor;

final class Qualifier
{
    public function __construct(protected Place $place, protected Competitor|null $competitor = null)
    {
    }

    public function getPlace(): Place
    {
        return $this->place;
    }

    public function getCompetitor(): Competitor|null
    {
        return $this->competitor;
    }

    public function setCompetitor(Competitor $competitor): void
    {
        $this->competitor = $competitor;
    }
}
