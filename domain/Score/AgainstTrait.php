<?php

namespace Sports\Score;

use Sports\Game\Against as AgainstGame;

trait AgainstTrait
{
    protected int $home;
    protected int $away;

    public function getHome(): int
    {
        return $this->home;
    }

    public function setHome(int $home)
    {
        $this->home = $home;
    }

    public function getAway(): int
    {
        return $this->away;
    }

    public function setAway(int $away)
    {
        $this->away = $away;
    }

    public function get(bool $homeAway): int
    {
        return $homeAway === AgainstGame::HOME ? $this->getHome() : $this->getAway();
    }

    public function getResult(bool $homeAway): int
    {
        if ($this->getHome() === $this->getAway()) {
            return AgainstGame::RESULT_DRAW;
        }
        return $this->get($homeAway) > $this->get(!$homeAway) ? AgainstGame::RESULT_WIN : AgainstGame::RESULT_LOST;
    }
}
