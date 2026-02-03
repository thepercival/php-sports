<?php

declare(strict_types=1);

namespace Sports\Score;

use SportsHelpers\Against\AgainstResult;
use SportsHelpers\Against\AgainstSide;

trait AgainstTrait
{
    protected float $home;
    protected float $away;

    public function getHome(): float
    {
        return $this->home;
    }

    public function setHome(float $home): void
    {
        $this->home = $home;
    }

    public function getAway(): float
    {
        return $this->away;
    }

    public function setAway(float $away): void
    {
        $this->away = $away;
    }

    public function get(AgainstSide $side): float
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
