<?php

namespace Sports\Game;

use Sports\Game;
use Sports\Place as PlaceBase;
use Sports\Team\Role\Player;

class Participation
{
    /**
     * @var int|string
     */
    protected $id;
    /**
     * @var Game
     */
    private $game;
    /**
     * @var Player
     */
    private $player;
    /**
     * @var int
     */
    private $beginMinute;
    /**
     * @var int
     */
    private $endMinute;

    public function __construct(Game $game, Player $player, int $beginMinute, int $endMinute )
    {
        $this->setGame($game);
        $this->player = $player;
        $this->beginMinute = $beginMinute;
        $this->endMinute = $endMinute;
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Player
     */
    public function getPlayer()
    {
        return $this->player;
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
    protected function setGame(Game $game)
    {
        if ($this->game === null and !$game->getParticipations()->contains($this)) {
            $game->getParticipations()->add($this) ;
        }
        $this->game = $game;
    }

    public function getBeginMinute(): int
    {
        return $this->beginMinute;
    }

    public function getEndMinute(): int
    {
        return $this->endMinute;
    }

    public function isBeginning(): bool
    {
        return $this->beginMinute === 0;
    }
}
