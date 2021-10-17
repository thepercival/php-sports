<?php

namespace Sports\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Game\Event\Card as CardEvent;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Sport;
use Sports\Team\Player;
use SportsHelpers\Identifiable;

class Participation extends Identifiable
{
    /**
     * @phpstan-var ArrayCollection<int|string, CardEvent>|PersistentCollection<int|string, CardEvent>
     * @psalm-var ArrayCollection<int|string, CardEvent>
     */
    protected ArrayCollection|PersistentCollection $cards;
    /**
     * @phpstan-var ArrayCollection<int|string, GoalEvent>|PersistentCollection<int|string, GoalEvent>
     * @psalm-var ArrayCollection<int|string, GoalEvent>
     */
    protected ArrayCollection|PersistentCollection $goalsAndAssists;

    public function __construct(
        protected AgainstGamePlace $againstGamePlace,
        protected Player $player,
        protected int $beginMinute,
        protected int $endMinute
    )
    {
        if (!$againstGamePlace->getParticipations()->contains($this)) {
            $againstGamePlace->getParticipations()->add($this) ;
        }
        $this->cards = new ArrayCollection();
        $this->goalsAndAssists = new ArrayCollection();
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getAgainstGamePlace(): AgainstGamePlace
    {
        return $this->againstGamePlace;
    }

    public function getBeginMinute(): int
    {
        return $this->beginMinute;
    }

    public function setBeginMinute(int $minute): void
    {
        $this->beginMinute = $minute;
    }

    public function isStarting(): bool
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

    public function hasAppeared(): bool
    {
        return $this->beginMinute > -1;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, CardEvent>|PersistentCollection<int|string, CardEvent>
     * @psalm-return ArrayCollection<int|string, CardEvent>
     */
    public function getCards(): ArrayCollection|PersistentCollection
    {
        return $this->cards;
    }

    /**
     * @return ArrayCollection<int|string, CardEvent>
     */
    public function getWarnings(): ArrayCollection
    {
        return $this->cards->filter(fn (CardEvent $card) => $card->getType() === Sport::WARNING);
    }

    public function getSendoff(): CardEvent|null
    {
        $sendOffCards = $this->cards->filter(fn (CardEvent $card) => $card->getType() === Sport::SENDOFF);
        $sendOffCard = $sendOffCards->first();
        return $sendOffCard === false ? null : $sendOffCard;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, GoalEvent>|PersistentCollection<int|string, GoalEvent>
     * @psalm-return ArrayCollection<int|string, GoalEvent>
     */
    public function getGoalsAndAssists(): ArrayCollection|PersistentCollection
    {
        return $this->goalsAndAssists;
    }

    /**
     * @return ArrayCollection<int|string, GoalEvent>
     */
    public function getGoals(): ArrayCollection
    {
        return $this->goalsAndAssists->filter(function (GoalEvent $goalEvent): bool {
            return $goalEvent->getGameParticipation() === $this;
        });
    }

    /**
     * @return ArrayCollection<int|string, GoalEvent>
     */
    public function getOwnGoals(): ArrayCollection
    {
        return $this->getGoals()->filter(fn (GoalEvent $goalEvent) => $goalEvent->getOwn());
    }

    /**
     * @return ArrayCollection<int|string, GoalEvent>
     */
    public function getPenalties(): ArrayCollection
    {
        return $this->getGoals()->filter(fn (GoalEvent $goalEvent) => $goalEvent->getPenalty());
    }

    /**
     * @return ArrayCollection<int|string, GoalEvent>
     */
    public function getFieldGoals(): ArrayCollection
    {
        return $this->getGoals()->filter(fn (GoalEvent $goal) => !$goal->getOwn() && !$goal->getPenalty());
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
