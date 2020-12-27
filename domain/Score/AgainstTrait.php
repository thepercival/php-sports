<?php

namespace Sports\Score;

use Sports\Game\Against as AgainstGame;

trait AgainstTrait
{
    protected int $homeScore;
    protected int $awayScore;

    public function getHomeScore(): int
    {
        return $this->homeScore;
    }

    public function setHomeScore(int $homeScore)
    {
        $this->homeScore = $homeScore;
    }

    public function getAwayScore(): int
    {
        return $this->awayScore;
    }

    public function setAwayScore(int $awayScore)
    {
        $this->awayScore = $awayScore;
    }

    public function get( bool $homeAway): int {
        return $homeAway === AgainstGame::HOME ? $this->getHomeScore() : $this->getAwayScore();
    }

    public function getResult( bool $homeAway ): int
    {
        if ($this->getHomeScore() === $this->getAwayScore()) {
            return AgainstGame::RESULT_DRAW;
        }
        return $this->get($homeAway) > $this->get(!$homeAway) ? AgainstGame::RESULT_WIN : AgainstGame::RESULT_LOST;
    }
}
