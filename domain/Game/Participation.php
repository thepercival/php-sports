<?php

namespace Sports\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Game\Event\Card as CardEvent;
use Sports\Game\Against as AgainstGame;
use Sports\Team\Player;
use SportsHelpers\Identifiable;

class Participation extends Identifiable
{
    /**
     * @var AgainstGame
     */
    private $againstGame;
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

    public function __construct(AgainstGame $game, Player $player, int $beginMinute, int $endMinute)
    {
        $this->setGame($game);
        $this->player = $player;
        $this->beginMinute = $beginMinute;
        $this->endMinute = $endMinute;
        $this->cards = new ArrayCollection();
        $this->goalsAndAssists = new ArrayCollection();
    }

    /**
     * @return Player
     */
    public function getPlayer()
    {
        return $this->player;
    }

    public function getGame(): AgainstGame
    {
        return $this->againstGame;
    }

    protected function setGame(AgainstGame $game): void
    {
        if (!$game->getParticipations()->contains($this)) {
            $game->getParticipations()->add($this) ;
        }
        $this->againstGame = $game;
    }

    public function getBeginMinute(): int
    {
        return $this->beginMinute;
    }

    public function setBeginMinute(int $minute): void
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

    public function setEndMinute(int $minute): void
    {
        $this->endMinute = $minute;
    }

    public function isSubstituted(): bool
    {
        return $this->endMinute > 0;
    }

    public function getCards(int $type = null): Collection
    {
        if ($type === null) {
            return $this->cards;
        }
        return $this->cards->filter(function (CardEvent $cardEvent) use ($type) : bool {
            return $cardEvent->getType() === $type;
        });
    }

    public function getGoalsAndAssists(): Collection
    {
        return $this->goalsAndAssists;
    }

    public function getGoals(int $type = null): Collection
    {
        return $this->goalsAndAssists->filter(function (GoalEvent $goalEvent) use ($type): bool {
            return $goalEvent->getGameParticipation() === $this && ($type === null || $goalEvent->isType($type));
        });
    }

    public function getAssists(): Collection
    {
        return $this->goalsAndAssists->filter(function (GoalEvent $goalEvent): bool {
            return $goalEvent->getAssistGameParticipation() === $this;
        });
    }
}
