<?php

declare(strict_types=1);

namespace Sports\Game\Place;

use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Event\Card as CardEvent;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Game\Event\Substitution as SubstitutionEvent;
use Sports\Game\Participation;
use Sports\Game\Place as GamePlaceBase;
use Sports\Person;
use Sports\Place as PlaceBase;

class Against extends GamePlaceBase
{
    /**
     * @var Collection<int|string, Participation>
     */
    protected Collection $participations;

    public function __construct(private AgainstGame $game, PlaceBase $place, private int $side)
    {
        parent::__construct($place);
        if (!$game->getPlaces()->contains($this)) {
            $game->getPlaces()->add($this) ;
        }
        $this->participations = new ArrayCollection();
    }

    public function getGame(): AgainstGame
    {
        return $this->game;
    }

    public function getSide(): int
    {
        return $this->side;
    }

    /**
     * @param Closure|null $filter
     * @psalm-param Closure(Participation=):bool|null $filter
     * @return Collection<int|string, Participation>
     */
    public function getParticipations(Closure|null $filter = null): Collection
    {
        if ($filter === null) {
            return $this->participations;
        }
        return $this->participations->filter($filter);
    }

    public function getParticipation(Person $person): Participation|null
    {
        $participations = $this->getParticipations(function (Participation $participation) use ($person): bool {
            return $participation->getPlayer()->getPerson() === $person;
        });
        $participation = $participations->first();
        return $participation !== false ? $participation : null;
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return list<Participation>
     */
    public function getSubstituted(TeamCompetitor $teamCompetitor = null): array
    {
        $substituted = $this->getParticipations(function (Participation $participation) use ($teamCompetitor): bool {
            return ($teamCompetitor === null || $participation->getPlayer()->getTeam() === $teamCompetitor->getTeam())
                && $participation->isSubstituted();
        })->toArray();
        uasort($substituted, function (Participation $participationA, Participation $participationB): int {
            return $participationA->getEndMinute() < $participationB->getEndMinute() ? -1 : 1;
        });
        return array_values($substituted);
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return list<GoalEvent>
     */
    public function getGoalEvents(TeamCompetitor $teamCompetitor = null): array
    {
        $goalEvents = [];
        $participations = $teamCompetitor !== null ? $this->getTeamParticipations($teamCompetitor) : $this->getParticipations();
        foreach ($participations as $participation) {
            $goalEvents = array_merge($goalEvents, $participation->getGoals()->toArray());
        }
        return array_values($goalEvents);
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return list<CardEvent>
     */
    public function getCardEvents(TeamCompetitor $teamCompetitor = null): array
    {
        $cardEvents = [];
        $participations = $teamCompetitor !== null ? $this->getTeamParticipations($teamCompetitor) : $this->getParticipations();
        foreach ($participations as $participation) {
            $cardEvents = array_merge($cardEvents, $participation->getCards()->toArray());
        }
        return array_values($cardEvents);
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return list<SubstitutionEvent>
     */
    public function getSubstituteEvents(TeamCompetitor $teamCompetitor = null): array
    {
        $substituteEvents = [];
        $substitutes = $this->getSubstitutes($teamCompetitor);
        $fncRemoveSubstitute = function (int $minute) use (&$substitutes): Participation|null {
            /** @var list<Participation> $substitutes */
            foreach ($substitutes as $substitute) {
                if ($substitute->getBeginMinute() === $minute) {
                    $substitutes = array_udiff(
                        $substitutes,
                        [$substitute],
                        function (Participation $a, Participation $b): int {
                            return $a === $b ? 0 : 1;
                        }
                    );
                    return $substitute;
                }
            }
            return null;
        };
        foreach ($this->getSubstituted($teamCompetitor) as $substituted) {
            $substitute = $fncRemoveSubstitute($substituted->getEndMinute());
            if ($substitute === null) {
                continue;
            }
            $substituteEvents[] = new SubstitutionEvent($substitute->getBeginMinute(), $substituted, $substitute);
        }
        return $substituteEvents;
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return array<GoalEvent|CardEvent|SubstitutionEvent>
     */
    public function getEvents(TeamCompetitor $teamCompetitor = null): array
    {
        return array_values(array_merge(
            $this->getGoalEvents($teamCompetitor),
            $this->getCardEvents($teamCompetitor),
            $this->getSubstituteEvents($teamCompetitor)
        ));
    }


    /**
     * @param TeamCompetitor $teamCompetitor
     * @return Collection<int|string, Participation>
     */
    public function getTeamParticipations(TeamCompetitor $teamCompetitor): Collection
    {
        return $this->getParticipations(function (Participation $participation) use ($teamCompetitor): bool {
            return $participation->getPlayer()->getTeam() === $teamCompetitor->getTeam();
        });
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return list<Participation>
     */
    public function getLineup(TeamCompetitor $teamCompetitor = null): array
    {
        $lineupParticipations = $this->getParticipations(function (Participation $participation) use ($teamCompetitor): bool {
            return ($teamCompetitor === null || $participation->getPlayer()->getTeam() === $teamCompetitor->getTeam())
                && $participation->isStarting();
        })->toArray();
        uasort($lineupParticipations, function (Participation $participationA, Participation $participationB): int {
            if ($participationA->getPlayer()->getLine() === $participationB->getPlayer()->getLine()) {
                return $participationA->getEndMinute() > $participationB->getEndMinute() ? -1 : 1;
            }
            return $participationA->getPlayer()->getLine() > $participationB->getPlayer()->getLine() ? -1 : 1;
        });
        return array_values($lineupParticipations);
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return list<Participation>
     */
    public function getSubstitutes(TeamCompetitor $teamCompetitor = null): array
    {
        $substitutes = $this->getParticipations(function (Participation $participation) use ($teamCompetitor): bool {
            return ($teamCompetitor === null || $participation->getPlayer()->getTeam() === $teamCompetitor->getTeam())
                && !$participation->isStarting();
        })->toArray();
        uasort($substitutes, function (Participation $participationA, Participation $participationB): int {
            return $participationA->getBeginMinute() < $participationB->getBeginMinute() ? -1 : 1;
        });
        return array_values($substitutes);
    }
}
