<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-6-18
 * Time: 15:18
 */

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
