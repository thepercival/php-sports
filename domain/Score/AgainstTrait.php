<?php

namespace Sports\Score;

use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Against\Result as AgainstResult;

trait AgainstTrait
{
    protected int $home;
    protected int $away;

    public function getHome(): int
    {
        return $this->home;
    }

    public function setHome(int $home): void
    {
        $this->home = $home;
    }

    public function getAway(): int
    {
        return $this->away;
    }

    public function setAway(int $away): void
    {
        $this->away = $away;
    }

    public function get(int $side): int
    {
        return $side === AgainstSide::HOME ? $this->getHome() : $this->getAway();
    }

    public function getResult(int $side): int
    {
        if ($this->getHome() === $this->getAway()) {
            return AgainstResult::DRAW;
        }
        $opposite = $side === AgainstSide::HOME ? AgainstSide::AWAY : AgainstSide::HOME;
        return $this->get($side) > $this->get($opposite) ? AgainstResult::WIN : AgainstResult::LOSS;
    }
}
