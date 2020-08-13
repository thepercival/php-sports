<?php

namespace Sports\Game\Score;

class HomeAway
{
    use HomeAwayTrait;

    public function __construct(int $home, int $away)
    {
        $this->setHome($home);
        $this->setAway($away);
    }
}
