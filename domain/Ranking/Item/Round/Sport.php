<?php

namespace Sports\Ranking\Item\Round;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Place\SportPerformance;
use SportsHelpers\PlaceLocationInterface;

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

    public function getPlaceLocation(): PlaceLocationInterface
    {
        return $this->performance->getPlaceLocation();
    }

    public function getPerformance(): SportPerformance
    {
        return $this->performance;
    }
}
