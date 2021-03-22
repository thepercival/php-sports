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
     * @var ArrayCollection<int|string, CardEvent>
     */
    private ArrayCollection $cards;
    /**
     * @var ArrayCollection<int|string, GoalEvent>
     */
    private ArrayCollection $goalsAndAssists;

    public function __construct(
        protected AgainstGame $againstGame,
        protected Player $player,
        protected int $beginMinute, protected int $endMinute)
    {
        if (!$againstGame->getParticipations()->contains($this)) {
            $againstGame->getParticipations()->add($this) ;
        }
        $this->cards = new ArrayCollection();
        $this->goalsAndAssists = new ArrayCollection();
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getGame(): AgainstGame
    {
        return $this->againstGame;
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

    /**
     * @param int|null $type
     * @return ArrayCollection<int|string, CardEvent>
     */
    public function getCards(int $type = null): ArrayCollection
    {
        if ($type === null) {
            return $this->cards;
        }
        return $this->cards->filter(function (CardEvent $cardEvent) use ($type) : bool {
            return $cardEvent->getType() === $type;
        });
    }

    /**
     * @return ArrayCollection<int|string, GoalEvent>
     */
    public function getGoalsAndAssists(): ArrayCollection
    {
        return $this->goalsAndAssists;
    }

    /**
     * @return ArrayCollection<int|string, GoalEvent>
     */
    public function getGoals(int $type = null): ArrayCollection
    {
        return $this->goalsAndAssists->filter(function (GoalEvent $goalEvent) use ($type): bool {
            return $goalEvent->getGameParticipation() === $this && ($type === null || $goalEvent->isType($type));
        });
    }

    /**
     * @return ArrayCollection<int|string, GoalEvent>
     */
    public function getAssists(): ArrayCollection
    {
        return $this->goalsAndAssists->filter(function (GoalEvent $goalEvent): bool {
            return $goalEvent->getAssistGameParticipation() === $this;
        });
    }
}
