<?php

namespace Sports\Qualify;

use Sports\Round;

abstract class RuleOld
{
    abstract public function getFromRound(): Round;
    abstract public function getWinnersOrLosers(): int;
    abstract public function getFromPlaceNumber(): int;
}
