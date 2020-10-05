<?php

namespace Sports\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Competitor\Team as TeamCompetitor;
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
    /**
     * @var Collection
     */
    private $cards;
    /**
     * @var Collection
     */
    private $goals;
    /**
     * @var Collection
     */
    private $assists;

    public function __construct(Game $game, Player $player, int $beginMinute, int $endMinute )
    {
        $this->setGame($game);
        $this->player = $player;
        $this->beginMinute = $beginMinute;
        $this->endMinute = $endMinute;
        $this->cards = new ArrayCollection();
        $this->goals = new ArrayCollection();
        $this->assists = new ArrayCollection();
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

    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function getGoals(): Collection
    {
        return $this->goals;
    }

    public function getAssists(): Collection
    {
        return $this->assists;
    }
}
