<?php

namespace Sports\Score;

class AgainstHelper
{
    use AgainstTrait;

    public function __construct(int $home, int $away)
    {
        $this->setHome($home);
        $this->setAway($away);
    }
}
