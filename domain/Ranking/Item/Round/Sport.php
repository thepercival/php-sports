<?php

namespace Sports\Ranking\Item\Round;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Place\Location as PlaceLocation;
use Sports\Place\SportPerformance;

class Sport
{
    public function __construct(
        private SportPerformance $performance,
        private int $uniqueRank,
        private int $rank
    ) {
    }

    public function getUniqueRank(): int
    {
        return $this->uniqueRank;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function getCompetitionSport(): CompetitionSport
    {
        return $this->getPerformance()->getCompetitionSport();
    }

    public function getPlaceLocation(): PlaceLocation
    {
        return $this->performance->getPlaceLocation();
    }

    public function getPerformance(): SportPerformance
    {
        return $this->performance;
    }
}
