<?php

declare(strict_types=1);

namespace Sports\Qualify;

use Sports\Competition\CompetitionSport as CompetitionSport;
use Sports\Ranking\PointsCalculation;
use Sports\Round;
use Sports\Sport;
use SportsHelpers\Identifiable;

class AgainstConfig extends Identifiable
{
    public function __construct(
        protected CompetitionSport $competitionSport,
        protected Round $round,
        protected PointsCalculation $pointsCalculation,
        protected float $winPoints,
        protected float $drawPoints,
        protected float $winPointsExt,
        protected float $drawPointsExt,
        protected float $losePointsExt
    ) {
        $this->round->getAgainstQualifyConfigs()->add($this);
    }

    public function getCompetitionSport(): CompetitionSport
    {
        return $this->competitionSport;
    }

    public function getCompetitionSportId(): string|int|null {
        return $this->competitionSport->getId();
    }

    public function getSport(): Sport
    {
        return $this->competitionSport->getSport();
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    public function getWinPoints(): float
    {
        return $this->winPoints;
    }

    public function setWinPoints(float $winPoints): void
    {
        $this->winPoints = $winPoints;
    }

    public function getDrawPoints(): float
    {
        return $this->drawPoints;
    }

    public function setDrawPoints(float $drawPoints): void
    {
        $this->drawPoints = $drawPoints;
    }

    public function getWinPointsExt(): float
    {
        return $this->winPointsExt;
    }

    public function setWinPointsExt(float $winPointsExt): void
    {
        $this->winPointsExt = $winPointsExt;
    }

    public function getDrawPointsExt(): float
    {
        return $this->drawPointsExt;
    }

    public function setDrawPointsExt(float $drawPointsExt): void
    {
        $this->drawPointsExt = $drawPointsExt;
    }

    public function getLosePointsExt(): float
    {
        return $this->losePointsExt;
    }

    public function setLosePointsExt(float $losePointsExt): void
    {
        $this->losePointsExt = $losePointsExt;
    }

    public function getPointsCalculation(): PointsCalculation
    {
        return $this->pointsCalculation;
    }

    public function setPointsCalculation(PointsCalculation $pointsCalculation): void
    {
        $this->pointsCalculation = $pointsCalculation;
    }
}
