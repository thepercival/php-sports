<?php

namespace Sports;

use DateTimeImmutable;
use \Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use League\Period\Period;
use Sports\Competition\Field;
use Sports\Competition\Referee;
use Sports\Game\Place as GamePlace;
use Sports\Planning\Config as PlanningConfig;
use Sports\Score\Config as ScoreConfig;
use Sports\Competition\Sport as CompetitionSport;
use SportsHelpers\Identifiable;

abstract class Game extends Identifiable
{
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
    /**
     * @var int
     */
    protected $state;

    public const PHASE_REGULARTIME = 1;
    public const PHASE_EXTRATIME = 2;
    public const PHASE_PENALTIES = 4;

    public const ORDER_BY_BATCH = 1;
    public const ORDER_BY_GAMEROUNDNUMBER = 2;

    public function __construct(
        protected Poule $poule,
        protected int $batchNr,
        protected DateTimeImmutable $startDateTime,
        protected CompetitionSport $competitionSport
    )
    {
        $this->setState(State::Created);
    }

    public function getPoule(): Poule
    {
        return $this->poule;
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
        return $this->refereePlace !== null ? $this->refereePlace->getRoundLocationId() : $this->refereePlaceLocId;
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

    public function getPlanningConfig(): PlanningConfig
    {
        return $this->getRound()->getNumber()->getValidPlanningConfig();
    }

    public function getScoreConfig(): ScoreConfig
    {
        return $this->getRound()->getValidScoreConfig($this->getCompetitionSport());
    }

    public function getPeriod(): Period
    {
        return new Period($this->getStartDateTime(), $this->getEndDateTime());
    }
}
