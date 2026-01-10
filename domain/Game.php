<?php

declare(strict_types=1);

namespace Sports;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Competition\Field;
use Sports\Competition\Referee;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Planning\Config as PlanningConfig;
use Sports\Score\Config as ScoreConfig;
use Sports\Structure\Locations\StructureLocationPlace;
use SportsHelpers\Identifiable;

abstract class Game extends Identifiable
{
    protected Referee|null $referee = null;
    protected Place|null $refereePlace = null;
    protected Field|null $field = null;
    protected Game\State $state;

    public function __construct(
        protected Poule $poule,
        protected int $batchNr,
        protected DateTimeImmutable $startDateTime,
        protected CompetitionSport $competitionSport
    ) {
        $this->setState(Game\State::Created);
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
        return $this->getStartDateTime()->add(new \DateInterval('PT' . $minutes . 'M'));
    }

    public function getCompetitionSport(): CompetitionSport
    {
        return $this->competitionSport;
    }

    public function getCompetitionSportId(): string|int
    {
        $competitionSportId = $this->getCompetitionSport()->getId();
        if ($competitionSportId === null) {
            throw new \Exception('competitionsport can not be null', E_ERROR);
        }
        return $competitionSportId;
    }

    public function getState(): Game\State
    {
        return $this->state;
    }

    final public function setState(Game\State $state): void
    {
        $this->state = $state;
    }

    public function getReferee(): ?Referee
    {
        return $this->referee;
    }

    public function getRefereeId(): int|string|null
    {
        return $this->referee?->getId();
    }

    public function setReferee(Referee|null $referee = null): void
    {
        $this->referee = $referee;
    }

    public function getRefereePlace(): ?Place
    {
        return $this->refereePlace;
    }

    public function getRefereeStructureLocation(): StructureLocationPlace|null
    {
        return $this->getRefereePlace()?->getStructureLocation();
    }

    public function setRefereePlace(Place|null $refereePlace = null): void
    {
        $this->refereePlace = $refereePlace;
    }

    public function getField(): ?Field
    {
        return $this->field;
    }

    public function getFieldId(): int|string|null
    {
        return $this->getField()?->getId();
    }

    /**
     * @param Field|null $field
     * @return void
     */
    public function setField(Field|null $field = null): void
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
        return Period::fromDate($this->getStartDateTime(), $this->getEndDateTime());
    }
}
