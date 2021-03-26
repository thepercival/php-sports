<?php

namespace Sports\Sport;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Competition;
use Sports\Competition\Field;
use Sports\Sport as SportBase;
use SportsHelpers\Identifiable;

class ConfigDep extends Identifiable
{
    protected float $winPoints = 0.0;
    protected float $drawPoints = 0.0;
    protected float $winPointsExt = 0.0;
    protected float $drawPointsExt = 0.0;
    protected float $losePointsExt = 0.0;
    /**
     * @var ArrayCollection<int|string, Field>
     */
    protected ArrayCollection $fieldsDep;
    protected int $pointsCalculationDep = 0;

    public function __construct(protected SportBase $sport, protected Competition $competition)
    {
        $this->fieldsDep = new ArrayCollection();
    }
}
