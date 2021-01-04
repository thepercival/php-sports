<?php

namespace Sports\Game;

use DateTimeImmutable;
use \Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Sports\Competitor;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game\Event\Card as CardEvent;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Game\Event\Substitution as SubstitutionEvent;
use Sports\Person;
use Sports\Place;
use Sports\Game as GameBase;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Place\Location\Map;
use Sports\Poule;
use Sports\Score\Against as AgainstScore;
use Sports\Competition\Sport as CompetitionSport;

class Against extends GameBase
{
    /**
     * @var AgainstGamePlace[] | Collection
     */
    protected $places;
    /**
     * @var AgainstScore[] | ArrayCollection
     */
    protected $scores;
    /**
     * @var Participation[] | Collection
     */
    protected $participations;
    protected int $gameRoundNumber;

    public const RESULT_WIN = 1;
    public const RESULT_DRAW = 2;
    public const RESULT_LOST = 3;

    public const HOME = true;
    public const AWAY = false;

    public function __construct(Poule $poule, int $batchNr, DateTimeImmutable $startDateTime, CompetitionSport $competitionSport)
    {
        parent::__construct($poule, $batchNr, $startDateTime, $competitionSport);
        $this->places = new ArrayCollection();
        $this->scores = new ArrayCollection();
        $this->participations = new ArrayCollection();
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
     * @return AgainstScore[] | ArrayCollection
     */
    public function getScores()
    {
        return $this->scores;
    }

    /**
     * @param bool|null $homeaway
     * @return Collection | AgainstGamePlace[]
     */
    public function getPlaces(bool $homeaway = null): Collection
    {
        if ($homeaway === null) {
            return $this->places;
        }
        return $this->places->filter(function ($gamePlace) use ($homeaway): bool {
                return $gamePlace->getHomeaway() === $homeaway;
            });
    }

//    /**
//     * @param \Sports\Place $place
//     * @param bool $homeaway
//     * @return GamePlace
//     */
//    public function addPlace(Place $place, bool $homeaway): GamePlace
//    {
//        return new GamePlace($this, $place, $homeaway);
//    }

    /**
     * @param Place $place
     * @param bool|null $homeaway
     * @return bool
     */
    public function isParticipating(Place $place, bool $homeaway = null): bool
    {
        $places = $this->getPlaces($homeaway)->map(function ($gamePlace) {
            return $gamePlace->getPlace();
        });
        return $places->contains($place);
    }

    public function getHomeAway(Place $place): ?bool
    {
        if ($this->isParticipating($place, self::HOME)) {
            return self::HOME;
        }
        if ($this->isParticipating($place, self::AWAY)) {
            return self::AWAY;
        }
        return null;
    }

    /**
     * @param Map $placeLocationMap
     * @param bool|null $homeAway
     * @return Collection|Competitor[]
     */
    public function getCompetitors( Map $placeLocationMap, bool $homeAway = null ): Collection {
        return $this->getPlaces( $homeAway )->map( function ( AgainstGamePlace $gamePlace ) use ($placeLocationMap) : Competitor {
            return $placeLocationMap->getCompetitor( $gamePlace->getPlace() );
        });
    }

    public function getFinalPhase(): int
    {
        if ($this->getScores()->count()  === 0) {
            return 0;
        }
        return $this->getScores()->last()->getPhase();
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return ArrayCollection|Collection|Participation[]
     */
    public function getParticipations(TeamCompetitor $teamCompetitor = null)
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
     * @return array|Participation[]
     */
    public function getLineup(TeamCompetitor $teamCompetitor = null): array
    {
        $lineupParticipations = $this->getFilteredParticipations(function (Participation $participation) use ($teamCompetitor): bool {
            return ($teamCompetitor === null || $participation->getPlayer()->getTeam() === $teamCompetitor->getTeam())
                && $participation->isBeginning();
        })->toArray();
        uasort( $lineupParticipations, function( Participation $participationA, Participation $participationB ): int {
            if( $participationA->getPlayer()->getLine() === $participationB->getPlayer()->getLine() ) {
                return $participationA->getEndMinute() > $participationB->getEndMinute() ? -1 : 1;
            }
            return $participationA->getPlayer()->getLine() > $participationB->getPlayer()->getLine() ? -1 : 1;
        });
        return $lineupParticipations;
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return array|Participation[]
     */
    public function getSubstitutes(TeamCompetitor $teamCompetitor = null): array
    {
        $substitutes = $this->getFilteredParticipations(function (Participation $participation) use ($teamCompetitor): bool {
            return ($teamCompetitor === null || $participation->getPlayer()->getTeam() === $teamCompetitor->getTeam() )
                && !$participation->isBeginning();
        })->toArray();
        uasort( $substitutes, function( Participation $participationA, Participation $participationB ): int {
            return $participationA->getBeginMinute() < $participationB->getBeginMinute() ? -1 : 1;
        });
        return $substitutes;
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return array|Participation[]
     */
    public function getSubstituted(TeamCompetitor $teamCompetitor = null): array
    {
        $substituted = $this->getFilteredParticipations(function (Participation $participation) use ($teamCompetitor): bool {
            return ($teamCompetitor === null || $participation->getPlayer()->getTeam() === $teamCompetitor->getTeam() )
                && $participation->isSubstituted();
        })->toArray();
        uasort( $substituted, function( Participation $participationA, Participation $participationB ): int {
            return $participationA->getEndMinute() < $participationB->getEndMinute() ? -1 : 1;
        });
        return $substituted;
    }

    public function getParticipation(Person $person ): ?Participation
    {
        $filtered = $this->getFilteredParticipations(function (Participation $participation) use ($person): bool {
            return $participation->getPlayer()->getPerson() === $person;
        });
        return $filtered->count() === 0 ? null : $filtered->first();
    }

    /**
     * @param callable $filter
     * @return ArrayCollection|Collection|Participation[]
     */
    protected function getFilteredParticipations( callable $filter )
    {
        return $this->participations->filter( $filter );
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return array|GoalEvent[]
     */
    public function getGoalEvents(TeamCompetitor $teamCompetitor = null): array
    {
        $goalEvents = [];
        foreach( $this->getParticipations($teamCompetitor) as $participation ) {
            $goalEvents = array_merge( $goalEvents, $participation->getGoals()->toArray() );
        }
        return $goalEvents;
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return array|CardEvent[]
     */
    public function getCardEvents(TeamCompetitor $teamCompetitor = null): array
    {
        $cardEvents = [];
        foreach( $this->getParticipations($teamCompetitor) as $participation ) {
            $cardEvents = array_merge( $cardEvents, $participation->getCards()->toArray() );
        }
        return $cardEvents;
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return array|SubstitutionEvent[]
     */
    public function getSubstituteEvents(TeamCompetitor $teamCompetitor = null): array
    {
        $substituteEvents = [];
        $substitutes = $this->getSubstitutes($teamCompetitor);
        $fncRemoveSubstitute = function ( $minute ) use(&$substitutes) : ?Participation {
            foreach( $substitutes as $substitute ) {
                if( $substitute->getBeginMinute() === $minute ) {
                    $substitutes = array_udiff( $substitutes, [$substitute],
                        function (Participation $a, Participation $b): int {
                            return $a === $b ? 0 : 1;
                        } );
                    return $substitute;
                }
            }
            return null;
        };
        foreach( $this->getSubstituted($teamCompetitor) as $substituted ) {
            $substitute = $fncRemoveSubstitute( $substituted->getEndMinute() );
            if( $substitute === null ) {
                continue;
            }
            $substituteEvents[] = new SubstitutionEvent( $substitute->getBeginMinute(), $substituted, $substitute );
        }
        return $substituteEvents;
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return array|GoalEvent[]|CardEvent[]
     */
    public function getEvents(TeamCompetitor $teamCompetitor = null): array
    {
        $events = array_merge(
            $this->getGoalEvents($teamCompetitor),
            $this->getCardEvents($teamCompetitor),
            $this->getSubstituteEvents($teamCompetitor)
        );
        uasort( $events, function ( $eventA, $eventB ): int {
            return $eventA->getMinute() < $eventB->getMinute() ? -1 : 1;
        });
        return $events;
    }
}
