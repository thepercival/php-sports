<?php

namespace Sports\Sport;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Sport as SportBase;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\Identifiable;

class ConfigDep extends Identifiable
{
    /**
     * @var SportBase
     */
    protected $sport;
    protected $competition;
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
     * @var ArrayCollection
     */
    protected $fieldsDep;
}
