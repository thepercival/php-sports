<?php

namespace Sports\Score;

class AgainstHelper
{
    use AgainstTrait;

    public function __construct(float $home, float $away)
    {
        $this->setHome($home);
        $this->setAway($away);
    }
}
