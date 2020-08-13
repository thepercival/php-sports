<?php

namespace Sports\Qualify;

use Sports\Round;

abstract class Rule
{
    abstract public function getFromRound(): Round;
    abstract public function isMultiple(): bool;
    abstract public function isSingle(): bool;
    abstract public function getWinnersOrLosers(): int;

    abstract public function getFromPlaceNumber(): int;
}
