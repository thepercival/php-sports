<?php
declare(strict_types=1);

namespace Sports;

use DateTimeImmutable;

use League\Period\Period;
use Sports\Competition\Field;
use Sports\Competition\Referee;
use Sports\Planning\Config as PlanningConfig;
use Sports\Score\Config as ScoreConfig;
use Sports\Competition\Sport as CompetitionSport;
use SportsHelpers\Identifiable;

abstract class Game extends Identifiable
{
    protected Referee|null $referee = null;
    protected Place|null $refereePlace = null;
    protected Field|null $field = null;
    protected int $state;

    private string|null $refereeStructureLocation = null; // json

    public function __construct(
        protected Poule $poule,
        protected int $batchNr,
        protected DateTimeImmutable $startDateTime,
        protected CompetitionSport $competitionSport
    ) {
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

    public function setStartDateTime(DateTimeImmutable $startDateTime): void
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

    final public function setState(int $state): void
    {
        $this->state = $state;
    }

    public function getReferee(): ?Referee
    {
        return $this->referee;
    }

    public function setReferee(Referee $referee = null): void
    {
        $this->referee = $referee;
    }

    public function getRefereePlace(): ?Place
    {
        return $this->refereePlace;
    }

    public function setRefereePlace(Place $refereePlace = null): void
    {
        $this->refereePlace = $refereePlace;
    }

    public function getField(): ?Field
    {
        return $this->field;
    }

    /**
     * @param Field|null $field
     * @return void
     */
    public function setField(Field $field = null): void
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

    public function getRefereeStructureLocation(): string|null
    {
        $refereePlace = $this->getRefereePlace();
        if ($refereePlace !== null) {
            return $refereePlace->getStructureLocation();
        }
        return $this->refereeStructureLocation;
    }

    public function setRefereeStructureLocation(string|null $refereeStructureLocation): void
    {
        $this->refereeStructureLocation = $refereeStructureLocation;
    }
}
