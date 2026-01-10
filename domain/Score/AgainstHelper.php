<?php

namespace Sports\Score;

final class AgainstHelper
{
    use AgainstTrait;

    public function __construct(float $home, float $away)
    {
        $this->setHome($home);
        $this->setAway($away);
    }
}
