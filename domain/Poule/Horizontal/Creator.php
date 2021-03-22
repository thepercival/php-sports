<?php

namespace Sports\Poule\Horizontal;

use Sports\Qualify\Group as QualifyGroup;

class Creator
{
    public function __construct(protected QualifyGroup $qualifyGroup, protected int $nrOfQualifiers)
    {
    }

    public function getQualifyGroup(): QualifyGroup
    {
        return $this->qualifyGroup;
    }

    public function getNrOfQualifiers(): int {
        return $this->nrOfQualifiers;
    }
}
