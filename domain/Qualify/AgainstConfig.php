<?php
declare(strict_types=1);

namespace Sports\Qualify;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Round\Number as RoundNumber;
use Sports\Sport as SportBase;
use SportsHelpers\Identifiable;

class AgainstConfig extends Identifiable
{
    protected CompetitionSport $competitionSport;
    protected RoundNumber $roundNumber;
    protected float $winPoints = 0.0;
    protected float $drawPoints = 0.0;
    protected float $winPointsExt = 0.0;
    protected float $drawPointsExt = 0.0;
    protected float $losePointsExt = 0.0;
    protected int $pointsCalculation = self::POINTS_CALC_POULEPOINTS;

    const DEFAULT_WINPOINTS = 3;
    const DEFAULT_DRAWPOINTS = 1;
    const POINTS_CALC_POULEPOINTS = 0;
    const POINTS_CALC_GAMESCORE = 1;
    const POINTS_CALC_BOTH = 2;

    public function __construct(CompetitionSport $competitionSport, RoundNumber $roundNumber)
    {
        $this->competitionSport = $competitionSport;
        $this->roundNumber = $roundNumber;
        $this->roundNumber->getQualifyAgainstConfigs()->add($this);
    }

    public function getCompetitionSport(): CompetitionSport
    {
        return $this->competitionSport;
    }

    public function getSport(): SportBase
    {
        return $this->competitionSport->getSport();
    }

    public function getRoundNumber(): RoundNumber
    {
        return $this->roundNumber;
    }

    public function getWinPoints(): float
    {
        return $this->winPoints;
    }

    public function setWinPoints(float $winPoints)
    {
        $this->winPoints = $winPoints;
    }

    public function getDrawPoints(): float
    {
        return $this->drawPoints;
    }

    public function setDrawPoints(float $drawPoints)
    {
        $this->drawPoints = $drawPoints;
    }

    public function getWinPointsExt(): float
    {
        return $this->winPointsExt;
    }

    public function setWinPointsExt(float $winPointsExt)
    {
        $this->winPointsExt = $winPointsExt;
    }

    public function getDrawPointsExt(): float
    {
        return $this->drawPointsExt;
    }

    public function setDrawPointsExt(float $drawPointsExt)
    {
        $this->drawPointsExt = $drawPointsExt;
    }

    public function getLosePointsExt(): float
    {
        return $this->losePointsExt;
    }

    public function setLosePointsExt(float $losePointsExt)
    {
        $this->losePointsExt = $losePointsExt;
    }

    public function getPointsCalculation(): int
    {
        return $this->pointsCalculation;
    }

    public function setPointsCalculation(int $pointsCalculation)
    {
        $this->pointsCalculation = $pointsCalculation;
    }
}