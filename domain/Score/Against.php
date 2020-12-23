<?php

namespace Sports\Score;

use Sports\Game\Against as AgainstGame;
use SportsHelpers\Identifiable;

class Against extends Identifiable
{
    private AgainstGame $game;
    protected int $number;
    protected int $phase;
    use AgainstTrait;

    const SCORED = 1;
    const RECEIVED = 2;

    public function __construct(AgainstGame $game, int $homeScore, int $awayScore, int $phase, int $number = null)
    {
        $this->setHomeScore($homeScore);
        $this->setAwayScore($awayScore);
        $this->setGame($game);
        if ($number === null) {
            $number = $game->getScores()->count();
        }
        $this->phase = $phase;
        $this->number = $number;
    }

    public function getGame(): AgainstGame
    {
        return $this->game;
    }

    protected function setGame(AgainstGame $game)
    {
        if ($this->game === null and !$game->getScores()->contains($this)) {
            $game->getScores()->add($this) ;
        }
        $this->game = $game;
    }

    public function getPhase(): int
    {
        return $this->phase;
    }

    public function getNumber(): int
    {
        return $this->number;
    }
}
