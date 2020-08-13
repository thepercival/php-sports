<?php

namespace Sports\Poule\Horizontal;

use Sports\Qualify\Group as QualifyGroup;

class Creator
{
    /**
     * @var QualifyGroup
     */
    public $qualifyGroup;
    /**
     * @var int
     */
    public $nrOfQualifiers;

    public function __construct(QualifyGroup $qualifyGroup, int $nrOfQualifiers)
    {
        $this->qualifyGroup = $qualifyGroup;
        $this->nrOfQualifiers = $nrOfQualifiers;
    }
}
