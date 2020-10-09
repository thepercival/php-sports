<?php

namespace Sports;

use DateTimeImmutable;
use \Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use League\Period\Period;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game\Event\Card as CardEvent;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Game\Score;
use Sports\Game\Participation;
use Sports\Game\Place as GamePlace;
use Sports\Planning\Config as PlanningConfig;
use Sports\Place\Location\Map;
use Sports\Sport\Config as SportConfig;
use Sports\Sport\ScoreConfig as SportScoreConfig;
use SportsHelpers\Identifiable;

class Game implements Identifiable
{
    /**
     * @var int|string
     */
    protected $id;
    /**
     * @var Poule
     */
    protected $poule;
    /**
     * @var int
     */
    protected $batchNr;
    /**
     * @var DateTimeImmutable
     */
    private $startDateTime;
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
    /**
     * @var int
     */
    protected $state;
    /**
     * @var Score[] | ArrayCollection
     */
    protected $scores;
    /**
     * @var GamePlace[] | Collection
     */
    protected $places;
    /**
     * @var Participation[] | Collection
     */
    protected $participations;

    public const RESULT_HOME = 1;
    public const RESULT_DRAW = 2;
    public const RESULT_AWAY = 3;

    public const HOME = true;
    public const AWAY = false;

    public const PHASE_REGULARTIME = 1;
    public const PHASE_EXTRATIME = 2;
    public const PHASE_PENALTIES = 4;

    public const ORDER_BY_BATCH = 1;
    public const ORDER_BY_GAMENUMBER = 2;

    public function __construct(Poule $poule, int $batchNr, \DateTimeImmutable $startDateTime)
    {
        $this->setPoule($poule);
        $this->batchNr = $batchNr;
        $this->startDateTime = $startDateTime;
        $this->setState(State::Created);
        $this->places = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->scores = new ArrayCollection();
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
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function setPoule(Poule $poule)
    {
        if ($this->poule === null and !$poule->getGames()->contains($this)) {
            $poule->getGames()->add($this) ;
        }
        $this->poule = $poule;
    }

    public function getRound(): Round
    {
        return $this->poule->getRound();
    }

    /**
     * @return int
     */
    public function getBatchNr(): int
    {
        return $this->batchNr;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getStartDateTime(): \DateTimeImmutable
    {
        return $this->startDateTime;
    }

    /**
     * @param \DateTimeImmutable $startDateTime
     */
    public function setStartDateTime(\DateTimeImmutable $startDateTime)
    {
        $this->startDateTime = $startDateTime;
    }

    public function getEndDateTime(): DateTimeImmutable
    {
        $minutes = $this->getPlanningConfig()->getMaxNrOfMinutesPerGame();
        return $this->getStartDateTime()->modify("+ " . $minutes . "minutes");
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

    /**
     * @return int
     */
    public function getRefereePriority()
    {
        return $this->referee !== null ? $this->referee->getPriority() : $this->refereePriority;
    }

    /**
     * @param int $refereePriority
     */
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
     * @param Field $field
     */
    public function setField(Field $field = null)
    {
        $this->field = $field;
    }

    /**
     * @return int
     */
    public function getFieldPriority()
    {
        return $this->field !== null ? $this->field->getPriority() : $this->fieldPriority;
    }

    /**
     * @param int $fieldPriority
     */
    public function setFieldPriority(int $fieldPriority = null)
    {
        $this->fieldPriority = $fieldPriority;
    }

    /**
     * @return Score[] | ArrayCollection
     */
    public function getScores()
    {
        return $this->scores;
    }

    /**
     * @param Score[] | ArrayCollection $scores
     */
    public function setScores($scores)
    {
        $this->scores = $scores;
    }

    /**
     * @param bool|null $homeaway
     * @return Collection | GamePlace[]
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

    /**
     * @param Collection | GamePlace[] $places
     */
    public function setPlaces(Collection $places)
    {
        $this->places = $places;
    }

    /**
     * @param \Sports\Place $place
     * @param bool $homeaway
     * @return GamePlace
     */
    public function addPlace(Place $place, bool $homeaway): GamePlace
    {
        return new GamePlace($this, $place, $homeaway);
    }

    /**
     * @param \Sports\Place $place
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
        if ($this->isParticipating($place, Game::HOME)) {
            return Game::HOME;
        }
        if ($this->isParticipating($place, Game::AWAY)) {
            return Game::AWAY;
        }
        return null;
    }

    /**
     * @param Map $placeLocationMap
     * @param bool|null $homeAway
     * @return Collection|Competitor[]
     */
    public function getCompetitors( Map $placeLocationMap, bool $homeAway = null ): Collection {
        return $this->getPlaces( $homeAway )->map( function ( GamePlace $gamePlace ) use ($placeLocationMap) : Competitor {
            return $placeLocationMap->getCompetitor( $gamePlace->getPlace() );
        });
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return Collection|Participation[]
     */
    public function getParticipations(TeamCompetitor $teamCompetitor = null): Collection
    {
        if ($teamCompetitor === null) {
            return $this->participations;
        }
        return $this->participations->filter(function (Participation $participation) use ($teamCompetitor): bool {
            return $participation->getPlayer()->getTeam() === $teamCompetitor->getTeam();
        });
    }

    public function getParticipation(Person $person ): ?Participation
    {
        return $this->participations->filter(function (Participation $participation) use ($person): bool {
            return $participation->getPlayer()->getPerson() === $person;
        })->first();
    }

    /**
     * @param TeamCompetitor|null $teamCompetitor
     * @return array|GoalEvent[]
     */
    public function getGoalEvents(TeamCompetitor $teamCompetitor = null): array
    {
        $goalEvents = [];
        foreach( $this->getParticipations($teamCompetitor) as $participation ) {
            $goalEvents = array_merge( $goalEvents, $participation->getGoalsAndAssists()->toArray() );
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
     * @return array|GoalEvent[]|CardEvent[]
     */
    public function getEvents(TeamCompetitor $teamCompetitor = null): array
    {
        $events = array_merge(
            $this->getGoalEvents($teamCompetitor),
            $this->getCardEvents($teamCompetitor)
        );
        /** @var GoalEvent|CardEvent $eventA */
        uasort( $events, function ( $eventA, $eventB ): int {
            return $eventA->getMinute() < $eventB->getMinute() ? -1 : 1;
        });
        return $events;
    }

    public function getFinalPhase(): int
    {
        if ($this->getScores()->count()  === 0) {
            return 0;
        }
        return $this->getScores()->last()->getPhase();
    }

    public function getPlanningConfig(): PlanningConfig
    {
        return $this->getRound()->getNumber()->getValidPlanningConfig();
    }

    public function getSportConfig(): SportConfig
    {
        $field = $this->getField();
        if ($field === null) {
            return $this->getRound()->getNumber()->getCompetition()->getFirstSportConfig();
        }
        return $this->getRound()->getNumber()->getCompetition()->getSportConfig($field->getSport());
    }

    public function getSportScoreConfig(): SportScoreConfig
    {
        $field = $this->getField();
        if ($field === null) {
            $sportScoreConfigs = $this->getRound()->getNumber()->getValidSportScoreConfigs();
            return reset($sportScoreConfigs);
        }
        // return $this->getRound()->getNumber()->getCompetition()->getSportConfig($field->getSport());
        return $this->getRound()->getNumber()->getValidSportScoreConfig($this->getField()->getSport());
    }

    public function getPeriod(): Period
    {
        return new Period( $this->getStartDateTime(), $this->getEndDateTime() );
    }
}
