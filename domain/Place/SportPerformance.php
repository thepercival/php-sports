<?php
declare(strict_types=1);

namespace Sports\Place;

use Sports\Place;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Place\Location as PlaceLocation;

class SportPerformance extends Performance
{
    public function __construct(
        private CompetitionSport $competitionSport,
        Place $place,
        int|null $penaltyPoints = null
    )
    {
        parent::__construct($place);
        if ($penaltyPoints !== null) {
            $this->addPoints(-$penaltyPoints);
        }
    }

    public function getCompetitionSport(): CompetitionSport
    {
        return $this->competitionSport;
    }
}
