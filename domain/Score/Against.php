<?php
declare(strict_types=1);

namespace Sports\Score;

use Sports\Game\Against as AgainstGame;
use Sports\Score;

class Against extends Score
{
    use AgainstTrait;

    const SCORED = 1;
    const RECEIVED = 2;

    public function __construct(protected AgainstGame $game, int $home, int $away, int $phase, int $number = null)
    {
        $this->setHome($home);
        $this->setAway($away);
        if (!$game->getScores()->contains($this)) {
            $game->getScores()->add($this) ;
        }
        if ($number === null) {
            $number = $game->getScores()->count();
        }
        parent::__construct($phase, $number);
    }

    public function getGame(): AgainstGame
    {
        return $this->game;
    }
}
