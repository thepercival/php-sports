<?php

namespace Sports\Game\Score;

use Sports\Game;

trait HomeAwayTrait
{
    /**
     * @var int
     */
    protected $home;

    /**
     * @var int
     */
    protected $away;

    /**
     * @return int
     */
    public function getHome(): int
    {
        return $this->home;
    }

    /**
     * @param int $home
     */
    public function setHome(int $home)
    {
        $this->home = $home;
    }

    /**
     * @return int
     */
    public function getAway(): int
    {
        return $this->away;
    }

    /**
     * @param int $away
     */
    public function setAway(int $away)
    {
        $this->away = $away;
    }

    public function get( bool $homeAway): int {
        return $homeAway === Game::HOME ? $this->getHome() : $this->getAway();
    }

    public function getResult( bool $homeAway ): int
    {
        if ($this->getHome() === $this->getAway()) {
            return Game::RESULT_DRAW;
        }
        return $this->get($homeAway) > $this->get(!$homeAway) ? Game::RESULT_WIN : Game::RESULT_LOST;
    }
}
