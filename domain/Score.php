<?php

namespace Sports;

use SportsHelpers\Identifiable;

abstract class Score extends Identifiable
{
    protected int $number;
    protected int $phase;

    public function __construct(int $phase, int $number)
    {
        $this->phase = $phase;
        $this->number = $number;
    }

    public function getPhase(): int
    {
        return $this->phase;
    }

    public function getNumber(): int
    {
        return $this->number;
    }
}
