<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-6-19
 * Time: 15:18
 */

namespace Sports\Sport;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Field;
use Sports\Sport as SportBase;
use Sports\Competition;
use SportsHelpers\SportConfig as SportConfigHelper;

class Config
{
    /**
     * @var SportBase
     */
    protected $sport;
    /**
     * @var Competition
     */
    protected $competition;
    /**
     * @var int
     */
    protected $id;
    /**
     * @var double
     */
    protected $winPoints;
    /**
     * @var double
     */
    protected $drawPoints;
    /**
     * @var double
     */
    protected $winPointsExt;
    /**
     * @var double
     */
    protected $drawPointsExt;
    /**
     * @var double
     */
    protected $losePointsExt;
    /**
     * @var int
     */
    protected $pointsCalculation;
    /**
     * @var int
     */
    protected $nrOfGamePlaces;
    /**
     * @var ArrayCollection
     */
    protected $fields;

    const DEFAULT_WINPOINTS = 3;
    const DEFAULT_DRAWPOINTS = 1;
    const POINTS_CALC_GAMEPOINTS = 0;
    const POINTS_CALC_SCOREPOINTS = 1;
    const POINTS_CALC_BOTH = 2;
    const DEFAULT_NROFGAMEPLACES = 2;

    public function __construct(SportBase $sport, Competition $competition)
    {
        $this->sport = $sport;
        $this->competition = $competition;
        $this->competition->getSportConfigs()->add($this);
        $this->fields = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id = null)
    {
        $this->id = $id;
    }

    /**
     * @return double
     */
    public function getWinPoints()
    {
        return $this->winPoints;
    }

    /**
     * @param double $winPoints
     */
    public function setWinPoints($winPoints)
    {
        $this->winPoints = $winPoints;
    }

    /**
     * @return double
     */
    public function getDrawPoints()
    {
        return $this->drawPoints;
    }

    /**
     * @param double $drawPoints
     */
    public function setDrawPoints($drawPoints)
    {
        $this->drawPoints = $drawPoints;
    }

    /**
     * @return double
     */
    public function getWinPointsExt()
    {
        return $this->winPointsExt;
    }

    /**
     * @param double $winPointsExt
     */
    public function setWinPointsExt($winPointsExt)
    {
        $this->winPointsExt = $winPointsExt;
    }

    /**
     * @return double
     */
    public function getDrawPointsExt()
    {
        return $this->drawPointsExt;
    }

    /**
     * @param double $drawPointsExt
     */
    public function setDrawPointsExt($drawPointsExt)
    {
        $this->drawPointsExt = $drawPointsExt;
    }

    /**
     * @return double
     */
    public function getLosePointsExt()
    {
        return $this->losePointsExt;
    }

    /**
     * @param double $losePointsExt
     */
    public function setLosePointsExt($losePointsExt)
    {
        $this->losePointsExt = $losePointsExt;
    }

    /**
     * @return int
     */
    public function getPointsCalculation(): int
    {
        return $this->pointsCalculation;
    }

    /**
     * @param int $pointsCalculation
     */
    public function setPointsCalculation(int $pointsCalculation)
    {
        $this->pointsCalculation = $pointsCalculation;
    }

    public function getNrOfGamePlaces(): int
    {
        return $this->nrOfGamePlaces;
    }

    public function setNrOfGamePlaces(int $nrOfGamePlaces): void
    {
        $this->nrOfGamePlaces = $nrOfGamePlaces;
    }

    /**
     * @return SportBase
     */
    public function getSport(): SportBase
    {
        return $this->sport;
    }

    public function setSport(SportBase $sport): void
    {
        $this->sport = $sport;
    }

    /**
     * @return Competition
     */
    public function getCompetition(): Competition
    {
        return $this->competition;
    }

    /**
     * @return ArrayCollection | Field[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param ArrayCollection | Field[] $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    public function getField(int $priority): ?Field
    {
        $fields = array_filter(
            $this->getFields()->toArray(),
            function (Field $field) use ($priority): bool {
                return $field->getPriority() === $priority;
            }
        );
        return count($fields) > 0 ? array_shift($fields) : null;
    }

    public function createHelper(): SportConfigHelper
    {
        return new SportConfigHelper($this->fields->count(), $this->nrOfGamePlaces);
    }
}
