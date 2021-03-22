<?php
declare(strict_types=1);

namespace Sports\Game;

use Closure;
use DateTimeImmutable;
use \Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use SportsHelpers\Against\Side as AgainstSide;
use Sports\Competitor;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game\Event\Card as CardEvent;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Game\Event\Substitution as SubstitutionEvent;
use Sports\Person;
use Sports\Place;
use Sports\Game as GameBase;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Competitor\Map as CompetitorMap;
use Sports\Poule;
use Sports\Qualify\AgainstConfig as QualifyConfig;
use Sports\Score\Against as AgainstScore;
use Sports\Competition\Sport as CompetitionSport;

class Against extends GameBase
{
    /**
     * @var ArrayCollection<int|string,AgainstGamePlace>
     */
    protected ArrayCollection $places;
    /**
     * @var ArrayCollection<int|string,AgainstScore>
     */
    protected ArrayCollection $scores;
    /**
     * @var ArrayCollection<int|string,Participation>
     */
    protected ArrayCollection $participations;
    protected int $gameRoundNumber = 0;

    public function __construct(Poule $poule, int $batchNr, DateTimeImmutable $startDateTime, CompetitionSport $competitionSport)
    {
        parent::__construct($poule, $batchNr, $startDateTime, $competitionSport);
        $this->places = new ArrayCollection();
        $this->scores = new ArrayCollection();
        $this->participations = new ArrayCollection();
        if (!$poule->getAgainstGames()->contains($this)) {
            $poule->getAgainstGames()->add($this);
        }
    }

//    public function getGameRoundNumber(): int
//    {
//        return $this->gameRoundNumber;
//    }
//
//    public function setGameRoundNumber(int $gameRoundNumber)
//    {
//        $this->gameRoundNumber = $gameRoundNumber;
//    }

    /**
     * @return ArrayCollection<int|string,AgainstScore>
     */
    public function getScores(): ArrayCollection
    {
        return $this->scores;
    }

    /**
     * @return ArrayCollection<int|string,AgainstGamePlace>
     */
    public function getPlaces(): ArrayCollection
    {
        return $this->places;
    }

    /**
     * @param int|null $side
     * @return list<AgainstGamePlace>
     */
    public function getSidePlaces(int $side = null): array
    {
        if ($side === AgainstSide::HOME) {
            return $this->getHomePlaces();
        } elseif ($side === AgainstSide::AWAY) {
            return $this->getAwayPlaces();
        }
        return array_values($this->getPlaces()->toArray());
    }

    /**
     * @return list<AgainstGamePlace>
     */
    public function getHomePlaces(): array
    {
        return $this->getSidePlacesHelper(AgainstSide::HOME);
    }

    /**
     * @return list<AgainstGamePlace>
     */
    public function getAwayPlaces(): array
    {
        return $this->getSidePlacesHelper(AgainstSide::AWAY);
    }

    /**
     * @return list<AgainstGamePlace>
     */
    protected function getSidePlacesHelper(int $side): array
    {
        return array_values($this->getPlaces()->filter(function (AgainstGamePlace $place) use ($side): bool {
            return $place->getSide() === $side;
        })->toArray());
    }

    public function getQualifyAgainstConfig(): QualifyConfig
    {
        return $this->getRound()->getValidQualifyAgainstConfig($this->getCompetitionSport());
    }

    /**
     * @param Place $place
     * @param int|null $side
     * @return bool
     */
    public function isParticipating(Place $place, int $side = null): bool
    {
        $places = array_map(function (AgainstGamePlace $gamePlace): Place {
            return $gamePlace->getPlace();
        }, $this->getSidePlaces($side));
        return array_search($place, $places, true) !== false;
    }

    public function getSide(Place $place): ?int
    {
        if ($this->isParticipating($place, AgainstSide::HOME)) {
            return AgainstSide::HOME;
        }
        if ($this->isParticipating($place, AgainstSide::AWAY)) {
            return AgainstSide::AWAY;
        }
        return null;
    }

    /**
     * @param CompetitorMap $competitorMap
     * @param int|null $side
     * @return list<Competitor|null>
     */
    public function getCompetitors(CompetitorMap $competitorMap, int $side = null): array
    {
        return array_values(array_map(
            function (AgainstGamePlace $gamePlace) use ($competitorMap): Competitor|null {
                return $competitorMap->getCompetitor($gamePlace->getPlace());
            },
            $this->getSidePlaces($side)
        ));
    }

    public function getFinalPhase(): int
    {
        $lastScore = $this->getScores()->last();
        return $lastScore !== false ? $lastScore->getPhase() : 0;
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return ArrayCollection<int|string, Participation>
     */
    public function getParticipations(TeamCompetitor $teamCompetitor = null): ArrayCollection
    {
        if ($teamCompetitor === null) {
            return $this->participations;
        }
        return $this->getFilteredParticipations(function (Participation $participation) use ($teamCompetitor): bool {
            return $participation->getPlayer()->getTeam() === $teamCompetitor->getTeam();
        });
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return list<Participation>
     */
    public function getLineup(TeamCompetitor $teamCompetitor = null): array
    {
        $lineupParticipations = $this->getFilteredParticipations(function (Participation $participation) use ($teamCompetitor): bool {
            return ($teamCompetitor === null || $participation->getPlayer()->getTeam() === $teamCompetitor->getTeam())
                && $participation->isBeginning();
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
        $substitutes = $this->getFilteredParticipations(function (Participation $participation) use ($teamCompetitor): bool {
            return ($teamCompetitor === null || $participation->getPlayer()->getTeam() === $teamCompetitor->getTeam())
                && !$participation->isBeginning();
        })->toArray();
        uasort($substitutes, function (Participation $participationA, Participation $participationB): int {
            return $participationA->getBeginMinute() < $participationB->getBeginMinute() ? -1 : 1;
        });
        return array_values($substitutes);
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return list<Participation>
     */
    public function getSubstituted(TeamCompetitor $teamCompetitor = null): array
    {
        $substituted = $this->getFilteredParticipations(function (Participation $participation) use ($teamCompetitor): bool {
            return ($teamCompetitor === null || $participation->getPlayer()->getTeam() === $teamCompetitor->getTeam())
                && $participation->isSubstituted();
        })->toArray();
        uasort($substituted, function (Participation $participationA, Participation $participationB): int {
            return $participationA->getEndMinute() < $participationB->getEndMinute() ? -1 : 1;
        });
        return array_values($substituted);
    }

    public function getParticipation(Person $person): ?Participation
    {
        $participations = $this->getFilteredParticipations(function (Participation $participation) use ($person): bool {
            return $participation->getPlayer()->getPerson() === $person;
        });
        $participation = $participations->first();
        return $participation !== false ? $participation : null;
    }

    /**
     * @param Closure $filter
     * @return ArrayCollection<int|string, Participation>
     */
    protected function getFilteredParticipations(Closure $filter): ArrayCollection
    {
        return $this->participations->filter($filter);
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return list<GoalEvent>
     */
    public function getGoalEvents(TeamCompetitor $teamCompetitor = null): array
    {
        $goalEvents = [];
        foreach ($this->getParticipations($teamCompetitor) as $participation) {
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
        foreach ($this->getParticipations($teamCompetitor) as $participation) {
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
        $fncRemoveSubstitute = function (int $minute) use (&$substitutes) : Participation|null {
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
        $events = array_merge(
            $this->getGoalEvents($teamCompetitor),
            $this->getCardEvents($teamCompetitor),
            $this->getSubstituteEvents($teamCompetitor)
        );
        uasort($events, function (
            GoalEvent|CardEvent|SubstitutionEvent $eventA,
            GoalEvent|CardEvent|SubstitutionEvent $eventB): int {
            return $eventA->getMinute() < $eventB->getMinute() ? -1 : 1;
        });
        return $events;
    }
}
