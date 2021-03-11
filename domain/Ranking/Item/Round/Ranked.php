<?php

namespace Sports\Ranking\Item\Round;

use Exception;
use Sports\Place;
use Sports\Competition\Sport as CompetitionSport;

class Ranked
{
    private int $uniqueRank = 1;
    private int $rank = 1;
    private int $cumulativeRank = 0;
    /**
     * @var SportRanked[]|array
     */
    private $sportItems = [];

    public function __construct(protected Place $place)
    {
    }

    public function getPlace(): Place
    {
        return $this->place;
    }

    public function getCumulativeRank(): int
    {
        return $this->cumulativeRank;
    }

    public function getUniqueRank(): int
    {
        return $this->uniqueRank;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function setRank(int $rank, int $uniqueRank)
    {
        $this->rank = $rank;
        $this->uniqueRank = $uniqueRank;
    }

    public function addSportRoundItem(SportRanked $item)
    {
        $this->sportItems[] = $item;
        $this->cumulativeRank += $item->getRank();
    }

    public function getSportItem(CompetitionSport $competitionSport): SportRanked
    {
        $sportItems = array_filter($this->sportItems, function (SportRanked $sportItemIt) use ($competitionSport): bool {
            return $sportItemIt->getCompetitionSport() === $competitionSport;
        });
        if (count($sportItems) === 0) {
            throw new Exception("sportItem could not be found", E_ERROR);
        }
        return reset($sportItems);
    }
}
