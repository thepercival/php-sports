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
use SportsHelpers\Against\AgainstSide;

/**
 * @api
 */
class Against extends GamePlaceBase
{
    /**
     * @var Collection<int|string, Participation>
     */
    protected Collection $participations;

    public function __construct(private AgainstGame $game, PlaceBase $place, private AgainstSide $side)
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

    public function getSide(): AgainstSide
    {
        return $this->side;
    }

    public function setSide(AgainstSide $side): void
    {
        $this->side = $side;
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
    public function getSubstituted(TeamCompetitor|null $teamCompetitor = null): array
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
    public function getGoalEvents(TeamCompetitor|null $teamCompetitor = null): array
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
    public function getCardEvents(TeamCompetitor|null $teamCompetitor = null): array
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
    public function getSubstituteEvents(TeamCompetitor|null $teamCompetitor = null): array
    {
        $substituteEvents = [];
        $substitutes = $this->getSubstitutes($teamCompetitor);

        foreach ($this->getSubstituted($teamCompetitor) as $substituted) {
            $substitute = $this->removeSubstitute($substitutes, $substituted->getEndMinute());
            if ($substitute === null) {
                continue;
            }
            $substituteEvents[] = new SubstitutionEvent($substitute->getBeginMinute(), $substituted, $substitute);
        }
        return $substituteEvents;
    }

    /**
     * @param list<Participation> $substitutes
     * @param int $minute
     * @return Participation|null
     */
    private function removeSubstitute(array &$substitutes, int $minute): Participation|null
    {
        $minuteSubstitutes = array_filter($substitutes, function (Participation $participation) use ($minute): bool {
            return $participation->getBeginMinute() === $minute;
        });
        $minuteSubstitute = array_shift($minuteSubstitutes);
        if ($minuteSubstitute === null) {
            return null;
        }

        $idx = array_search($minuteSubstitute, $substitutes, true);
        if ($idx === false) {
            return null;
        }
        array_splice($substitutes, $idx, 1);
        return $minuteSubstitute;
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return list<GoalEvent|CardEvent|SubstitutionEvent>
     */
    public function getEvents(TeamCompetitor|null $teamCompetitor = null): array
    {
        return array_merge(
            $this->getGoalEvents($teamCompetitor),
            $this->getCardEvents($teamCompetitor),
            $this->getSubstituteEvents($teamCompetitor)
        );
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
    public function getLineup(TeamCompetitor|null $teamCompetitor = null): array
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
    public function getSubstitutes(TeamCompetitor|null $teamCompetitor = null): array
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
