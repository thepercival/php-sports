<?php

declare(strict_types=1);

namespace Sports\Ranking\Item;

use Exception;
use Sports\Place;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Place\Performance as PlacePerformance;
use Sports\Ranking\Item\Round as RoundRankingItem;
use Sports\Ranking\Item\Round\Sport as SportRoundRankingItem;

class Round
{
    private int $uniqueRank = 1;
    private int $rank = 1;
    private int $cumulativeRank = 0;
    private PlacePerformance $cumulativePerformance;
    /**
     * @var array<SportRoundRankingItem>
     */
    private $sportItems = [];

    public function __construct(protected Place $place)
    {
        $this->cumulativePerformance = new PlacePerformance($place);
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

    public function getCumulativePerformance(): PlacePerformance
    {
        return $this->cumulativePerformance;
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
        $this->cumulativePerformance->addSportPerformace($item->getPerformance());
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

    public function compareCumulativePerformances(RoundRankingItem $roundRankingItem): float
    {
        $otherPerformance = $roundRankingItem->getCumulativePerformance();

        $cmpPoints = $otherPerformance->getPoints() - $this->cumulativePerformance->getPoints();
        if ($cmpPoints != 0) {
            return $cmpPoints;
        }

        $cmpGames = $this->cumulativePerformance->getGames() - $otherPerformance->getGames();
        if ($cmpGames != 0) {
            return $cmpGames;
        }

        $cmpDiff = $otherPerformance->getDiff() - $this->cumulativePerformance->getDiff();
        if ($cmpDiff != 0) {
            return $cmpDiff;
        }

        $cmpScored = $otherPerformance->getScored() - $this->cumulativePerformance->getScored();
        if ($cmpScored != 0) {
            return $cmpScored;
        }

        $cmpReceived = $this->cumulativePerformance->getReceived() - $otherPerformance->getReceived();
        if ($cmpReceived != 0) {
            return $cmpReceived;
        }

        $cmpSubDiff = $otherPerformance->getSubDiff() - $this->cumulativePerformance->getSubDiff();
        if ($cmpSubDiff != 0) {
            return $cmpSubDiff;
        }

        $cmpSubScored = $otherPerformance->getSubScored() - $this->cumulativePerformance->getSubScored();
        if ($cmpSubScored != 0) {
            return $cmpSubScored;
        }

        return $this->cumulativePerformance->getSubReceived() - $otherPerformance->getSubReceived();
    }
}
