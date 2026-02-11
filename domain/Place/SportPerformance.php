<?php

declare(strict_types=1);

namespace Sports\Place;

use Sports\Place;
use Sports\Competition\CompetitionSport as CompetitionSport;

final class SportPerformance extends Performance
{
    public function __construct(
        private CompetitionSport $competitionSport,
        Place $place,
        int|null $extraPoints = null
    ) {
        parent::__construct($place);
        if ($extraPoints !== null) {
            $this->addPoints($extraPoints);
        }
    }

    public function getCompetitionSport(): CompetitionSport
    {
        return $this->competitionSport;
    }
}
