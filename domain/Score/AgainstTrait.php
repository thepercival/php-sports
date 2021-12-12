<?php

declare(strict_types=1);

namespace Sports\Score;

use SportsHelpers\Against\Result as AgainstResult;
use SportsHelpers\Against\Side as AgainstSide;

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

    public function get(AgainstSide $side): int
    {
        return $side === AgainstSide::Home ? $this->getHome() : $this->getAway();
    }

    public function getResult(AgainstSide $side): AgainstResult
    {
        if ($this->getHome() === $this->getAway()) {
            return AgainstResult::Draw;
        }
        return $this->get($side) > $this->get($side->getOpposite()) ? AgainstResult::Win : AgainstResult::Loss;
    }
}
