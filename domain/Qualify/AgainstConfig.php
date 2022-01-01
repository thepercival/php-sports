<?php

declare(strict_types=1);

namespace Sports\Qualify;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Ranking\PointsCalculation;
use Sports\Round;
use Sports\Sport;
use SportsHelpers\Identifiable;

class AgainstConfig extends Identifiable
{
    protected float $winPoints = 0.0;
    protected float $drawPoints = 0.0;
    protected float $winPointsExt = 0.0;
    protected float $drawPointsExt = 0.0;
    protected float $losePointsExt = 0.0;

    public const DEFAULT_WINPOINTS = 3;
    public const DEFAULT_DRAWPOINTS = 1;

    public function __construct(
        protected CompetitionSport $competitionSport,
        protected Round $round,
        protected PointsCalculation $pointsCalculation
    ) {
        $this->round->getAgainstQualifyConfigs()->add($this);
    }

    public function getCompetitionSport(): CompetitionSport
    {
        return $this->competitionSport;
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

    public function getPointsCalculationNative(): int
    {
        return $this->pointsCalculation->value;
    }

    public function setPointsCalculationNative(int $pointsCalculation): void
    {
        /** @psalm-suppress MixedAssignment, UndefinedMethod */
        $this->pointsCalculation = PointsCalculation::from($pointsCalculation);
    }
}
