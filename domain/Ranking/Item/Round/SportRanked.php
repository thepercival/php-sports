<?php

namespace Sports\Ranking\Item\Round;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Place\Location as PlaceLocation;

class SportRanked
{
    public function __construct(
        private SportUnranked $unranked,
        private int $uniqueRank,
        private int $rank
    )
    {
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
        return $this->getUnranked()->getCompetitionSport();
    }

    public function getPlaceLocation(): PlaceLocation
    {
        return $this->unranked->getPlaceLocation();
    }

    public function getUnranked(): SportUnranked
    {
        return $this->unranked;
    }
}
