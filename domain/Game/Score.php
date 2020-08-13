<?php

namespace Sports\Game;

use Sports\Game;

class Score
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var Game
     */
    private $game;
    /**
     * @var int
     */
    private $phase;
    /**
     * @var int
     */
    private $number;

    use Score\HomeAwayTrait;

    const SCORED = 1;
    const RECEIVED = 2;

    public function __construct(Game $game, int $home, int $away, int $phase, int $number = null)
    {
        $this->setHome($home);
        $this->setAway($away);
        $this->setGame($game);
        $this->setPhase($phase);
        if ($number === null) {
            $number = $game->getScores()->count();
        }
        $this->setNumber($number);
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return Game
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * @param Game $game
     */
    public function setGame(Game $game)
    {
        if ($this->game === null and !$game->getScores()->contains($this)) {
            $game->getScores()->add($this) ;
        }
        $this->game = $game;
    }

    /**
     * @return int
     */
    public function getPhase()
    {
        return $this->phase;
    }

    /**
     * @param int $phase
     */
    public function setPhase($phase)
    {
        $this->phase = $phase;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }
}
