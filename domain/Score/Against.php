<?php

namespace Sports\Score;

use Sports\Game\Against as AgainstGame;
use Sports\Score;

class Against extends Score
{
    private AgainstGame $game;
    use AgainstTrait;

    const SCORED = 1;
    const RECEIVED = 2;

    public function __construct(AgainstGame $game, int $home, int $away, int $phase, int $number = null)
    {
        $this->setHome($home);
        $this->setAway($away);
        $this->setGame($game);
        if ($number === null) {
            $number = $game->getScores()->count();
        }
        parent::__construct($phase, $number);
    }

    public function getGame(): AgainstGame
    {
        return $this->game;
    }

    protected function setGame(AgainstGame $game): void
    {
        if (!$game->getScores()->contains($this)) {
            $game->getScores()->add($this) ;
        }
        $this->game = $game;
    }
}
