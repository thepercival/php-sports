<?php

namespace Sports\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Team\Player;

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
    private $goalsAndAssists;

    public function __construct(Game $game, Player $player, int $beginMinute, int $endMinute )
    {
        $this->setGame($game);
        $this->player = $player;
        $this->beginMinute = $beginMinute;
        $this->endMinute = $endMinute;
        $this->cards = new ArrayCollection();
        $this->goalsAndAssists = new ArrayCollection();
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

    public function setBeginMinute( int $minute )
    {
        $this->beginMinute = $minute;
    }

    public function isBeginning(): bool
    {
        return $this->beginMinute === 0;
    }

    public function getEndMinute(): int
    {
        return $this->endMinute;
    }

    public function setEndMinute( int $minute )
    {
        $this->endMinute = $minute;
    }

    public function isSubstituted(): bool
    {
        return $this->endMinute > 0;
    }

    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function getGoalsAndAssists(): Collection
    {
        return $this->goalsAndAssists;
    }

    public function getGoals(): Collection
    {
        return $this->goalsAndAssists->filter( function( GoalEvent $goalEvent ): bool {
            return $goalEvent->getGameParticipation() === $this;
        });
    }

    public function getAssists(): Collection
    {
        return $this->goalsAndAssists->filter( function( GoalEvent $goalEvent ): bool {
            return $goalEvent->getAssistGameParticipation() === $this;
        });
    }
}
