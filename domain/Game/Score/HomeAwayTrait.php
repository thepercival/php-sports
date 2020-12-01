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
        if( $homeAway === Game::HOME ) {
            return ($this->getHome() > $this->getAway()) ? Game::RESULT_WIN : Game::RESULT_LOST;
        }
        return ($this->getAway() > $this->getHome()) ? Game::RESULT_WIN : Game::RESULT_LOST;
    }
}
