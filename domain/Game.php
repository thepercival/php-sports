<?php

namespace Sports;

use DateTimeImmutable;
use \Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use League\Period\Period;
use Sports\Competition\Field;
use Sports\Competition\Referee;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game\Event\Card as CardEvent;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Game\Event\Substitution as SubstitutionEvent;
use Sports\Game\Participation;
use Sports\Game\Place as GamePlace;
use Sports\Planning\Config as PlanningConfig;
use Sports\Qualify\Config as QualifyConfig;
use Sports\Sport\ScoreConfig as SportScoreConfig;
use Sports\Competition\Sport as CompetitionSport;
use SportsHelpers\Identifiable;

abstract class Game extends Identifiable
{
    protected Poule $poule;
    protected int $batchNr;
    private DateTimeImmutable $startDateTime;
    /**
     * @var Referee
     */
    protected $referee;
    protected $refereePriority; // for serialization, not used
    /**
     * @var Place
     */
    protected $refereePlace;
    protected $refereePlaceLocId; // for serialization, not used
    /**
     * @var ?Field
     */
    protected $field;
    protected $fieldPriority; // for serialization, not used
    protected CompetitionSport $competitionSport;
    /**
     * @var int
     */
    protected $state;
    /**
     * @var GamePlace[] | Collection
     */
    protected $places;
    /**
     * @var Participation[] | Collection
     */
    protected $participations;

    public const PHASE_REGULARTIME = 1;
    public const PHASE_EXTRATIME = 2;
    public const PHASE_PENALTIES = 4;

    public const ORDER_BY_BATCH = 1;
    public const ORDER_BY_GAMEROUNDNUMBER = 2;

    public function __construct(Poule $poule, int $batchNr, DateTimeImmutable $startDateTime, CompetitionSport $competitionSport)
    {
        $this->setPoule($poule);
        $this->batchNr = $batchNr;
        $this->startDateTime = $startDateTime;
        $this->competitionSport = $competitionSport;
        $this->setState(State::Created);
        $this->places = new ArrayCollection();
        $this->participations = new ArrayCollection();
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function setPoule(Poule $poule)
    {
        if (!$poule->getGames()->contains($this)) {
            $poule->getGames()->add($this) ;
        }
        $this->poule = $poule;
    }

    public function getRound(): Round
    {
        return $this->poule->getRound();
    }

    public function getBatchNr(): int
    {
        return $this->batchNr;
    }

    public function getStartDateTime(): DateTimeImmutable
    {
        return $this->startDateTime;
    }

    public function setStartDateTime(DateTimeImmutable $startDateTime)
    {
        $this->startDateTime = $startDateTime;
    }

    public function getEndDateTime(): DateTimeImmutable
    {
        $minutes = $this->getPlanningConfig()->getMaxNrOfMinutesPerGame();
        return $this->getStartDateTime()->modify("+ " . $minutes . "minutes");
    }

    public function getCompetitionSport(): CompetitionSport
    {
        return $this->competitionSport;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function setState(int $state)
    {
        $this->state = $state;
    }

    public function getReferee(): ?Referee
    {
        return $this->referee;
    }

    public function setReferee(Referee $referee = null)
    {
        $this->referee = $referee;
    }

    public function getRefereePriority(): ?int
    {
        return $this->referee !== null ? $this->referee->getPriority() : $this->refereePriority;
    }

    public function setRefereePriority(int $refereePriority = null)
    {
        $this->refereePriority = $refereePriority;
    }

    public function getRefereePlace(): ?Place
    {
        return $this->refereePlace;
    }

    public function setRefereePlace(Place $refereePlace = null)
    {
        $this->refereePlace = $refereePlace;
    }

    public function getRefereePlaceLocId(): ?string
    {
        return $this->refereePlace !== null ? $this->refereePlace->getLocationId() : $this->refereePlaceLocId;
    }

    public function setRefereePlaceLocId(string $refereePlaceLocId = null)
    {
        $this->refereePlaceLocId = $refereePlaceLocId;
    }

    /**
     * @return ?Field
     */
    public function getField(): ?Field
    {
        return $this->field;
    }

    /**
     * @param Field|null $field
     */
    public function setField(Field $field = null)
    {
        $this->field = $field;
    }

    public function getFieldPriority(): int
    {
        return $this->field !== null ? $this->field->getPriority() : $this->fieldPriority;
    }

    public function setFieldPriority(int $fieldPriority = null)
    {
        $this->fieldPriority = $fieldPriority;
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

    public function getPlanningConfig(): PlanningConfig
    {
        return $this->getRound()->getNumber()->getValidPlanningConfig();
    }

    public function getSportScoreConfig(): SportScoreConfig
    {
        return $this->getRound()->getNumber()->getValidSportScoreConfig($this->getCompetitionSport() );
    }

    public function getQualifyConfig(): QualifyConfig
    {
        return $this->getRound()->getNumber()->getValidQualifyConfig($this->getCompetitionSport() );
    }

    public function getPeriod(): Period
    {
        return new Period( $this->getStartDateTime(), $this->getEndDateTime() );
    }
}
