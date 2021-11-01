<?php

namespace Sports\Game;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Game\Event\Card as CardEvent;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Sport;
use Sports\Team\Player;
use SportsHelpers\Identifiable;

class Participation extends Identifiable
{
    /**
     * @var Collection<int|string, CardEvent>
     */
    protected Collection $cards;
    /**
     * @var Collection<int|string, GoalEvent>
     */
    protected Collection $goalsAndAssists;

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
     * @return Collection<int|string, CardEvent>
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    /**
     * @return Collection<int|string, CardEvent>
     */
    public function getWarnings(): Collection
    {
        return $this->cards->filter(fn(CardEvent $card) => $card->getType() === Sport::WARNING);
    }

    public function getSendoff(): CardEvent|null
    {
        $sendOffCards = $this->cards->filter(fn (CardEvent $card) => $card->getType() === Sport::SENDOFF);
        $sendOffCard = $sendOffCards->first();
        return $sendOffCard === false ? null : $sendOffCard;
    }

    /**
     * @return Collection<int|string, GoalEvent>
     */
    public function getGoalsAndAssists(): Collection
    {
        return $this->goalsAndAssists;
    }

    /**
     * @return Collection<int|string, GoalEvent>
     */
    public function getGoals(): Collection
    {
        return $this->goalsAndAssists->filter(function (GoalEvent $goalEvent): bool {
            return $goalEvent->getGameParticipation() === $this;
        });
    }

    /**
     * @return Collection<int|string, GoalEvent>
     */
    public function getOwnGoals(): Collection
    {
        return $this->getGoals()->filter(fn(GoalEvent $goalEvent) => $goalEvent->getOwn());
    }

    /**
     * @return Collection<int|string, GoalEvent>
     */
    public function getPenalties(): Collection
    {
        return $this->getGoals()->filter(fn(GoalEvent $goalEvent) => $goalEvent->getPenalty());
    }

    /**
     * @return Collection<int|string, GoalEvent>
     */
    public function getFieldGoals(): Collection
    {
        return $this->getGoals()->filter(fn(GoalEvent $goal) => !$goal->getOwn() && !$goal->getPenalty());
    }

    /**
     * @return Collection<int|string, GoalEvent>
     */
    public function getAssists(): Collection
    {
        return $this->goalsAndAssists->filter(function (GoalEvent $goalEvent): bool {
            return $goalEvent->getAssistGameParticipation() === $this;
        });
    }
}
