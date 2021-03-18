<?php

namespace Sports\Ranking\Item;

use Exception;
use Sports\Place;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Ranking\Item\Round\Sport as SportRoundRankingItem;

class Round
{
    private int $uniqueRank = 1;
    private int $rank = 1;
    private int $cumulativeRank = 0;
    /**
     * @var array<SportRoundRankingItem>
     */
    private $sportItems = [];

    public function __construct(protected Place $place)
    {
    }

    public function getPlace(): Place
    {
        return $this->place;
    }

    public function getRoundLocationId(): string
    {
        return $this->place->getRoundLocationId();
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

    public function setRank(int $rank, int $uniqueRank): void
    {
        $this->rank = $rank;
        $this->uniqueRank = $uniqueRank;
    }

    public function addSportRoundItem(SportRoundRankingItem $item): void
    {
        $this->sportItems[] = $item;
        $this->cumulativeRank += $item->getRank();
    }

    public function getSportItem(CompetitionSport $competitionSport): SportRoundRankingItem
    {
        $sportItems = array_filter($this->sportItems, function (SportRoundRankingItem $rankingItem) use ($competitionSport): bool {
            return $rankingItem->getCompetitionSport() === $competitionSport;
        });
        if (count($sportItems) === 0) {
            throw new Exception("sportItem could not be found", E_ERROR);
        }
        return reset($sportItems);
    }
}
