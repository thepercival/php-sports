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

    public function getResult(): int
    {
        if ($this->getHome() === $this->getAway()) {
            return Game::RESULT_DRAW;
        }
        return ($this->getHome() > $this->getAway()) ? Game::RESULT_HOME : Game::RESULT_AWAY;
    }
}
