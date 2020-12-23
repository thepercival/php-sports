<?php

namespace Sports\Score;

use Sports\Game;

trait AgainstTrait
{
    protected int $homeScore;
    protected int $awayScore;

    /**
     * @return int
     */
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
        return $homeAway === Game::HOME ? $this->getHomeScore() : $this->getAwayScore();
    }

    public function getResult( bool $homeAway ): int
    {
        if ($this->getHomeScore() === $this->getAwayScore()) {
            return Game::RESULT_DRAW;
        }
        return $this->get($homeAway) > $this->get(!$homeAway) ? Game::RESULT_WIN : Game::RESULT_LOST;
    }
}
