<?php

namespace Sports\Score;

class AgainstHelper
{
    use AgainstTrait;

    public function __construct(int $homeScore, int $awayScore)
    {
        $this->setHomeScore($homeScore);
        $this->setAwayScore($awayScore);
    }
}
