<?php

namespace Sports\Qualify\Rule;

use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Rule as QualifyRule;

abstract class Vertical extends QualifyRule
{
    public function __construct(
        HorizontalPoule $fromHorizontalPoule) {
        parent::__construct($fromHorizontalPoule);
    }
}