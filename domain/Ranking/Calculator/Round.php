<?php

declare(strict_types=1);

namespace Sports\Ranking\Calculator;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Poule;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Place;
use Sports\Ranking\Item\Round as RoundRankingItem;
use Sports\Ranking\Item\Round\Sport as SportRoundRankingItem;
use Sports\State;
use Sports\Ranking\Calculator\Round\Sport as SportRoundRankingCalculator;
use Sports\Ranking\Calculator\Round\Sport\Against as AgainstSportRoundRankingCalculator;
use Sports\Ranking\Calculator\Round\Sport\Together as TogetherSportRoundRankingCalculator;
use SportsHelpers\GameMode;

class Round
{
    /**
     * @var array<int>
     */
    protected array $gameStates;

    public function __construct(array $gameStates = null)
    {
        $this->gameStates = $gameStates ?? [State::Finished];
    }

    protected function getSportRoundRankingCalculator(CompetitionSport $competitionSport): SportRoundRankingCalculator
    {
        if ($competitionSport->getSport()->getGameMode() === GameMode::AGAINST) {
            return new AgainstSportRoundRankingCalculator($competitionSport, $this->gameStates);
        }
        return new TogetherSportRoundRankingCalculator($competitionSport, $this->gameStates);
    }


    /**
     * @param Poule $poule
     * @return array<RoundRankingItem>
     */
    public function getItemsForPoule(Poule $poule): array
    {
        $sportRoundRankingItems = $poule->getCompetition()->getSports()->map(function (CompetitionSport $competitionSport) use ($poule): array {
            return $this->getSportRoundRankingCalculator($competitionSport)->getItemsForPoule($poule);
        })->toArray();
        return $this->convertSportRoundRankingsToRoundItems($poule->getPlaces()->toArray(), $sportRoundRankingItems);
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @return array<Place>
     */
    public function getPlacesForHorizontalPoule(HorizontalPoule $horizontalPoule): array
    {
        return array_map(function (RoundRankingItem $rankingItem): Place {
            return $rankingItem->getPlace();
        }, $this->getItemsForHorizontalPoule($horizontalPoule, true));
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @return array<Place>
     */
    public function getPlaceLocationsForHorizontalPoule(HorizontalPoule $horizontalPoule): array
    {
        return $this->getPlacesForHorizontalPoule($horizontalPoule);
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @param bool|null $checkOnSingleQualifyRule
     * @return array<RoundRankingItem>
     */
    public function getItemsForHorizontalPoule(HorizontalPoule $horizontalPoule, bool $checkOnSingleQualifyRule = null): array
    {
        $competitionSports = $horizontalPoule->getRound()->getNumber()->getCompetitionSports();
        $sportRoundRankingItems = $competitionSports->map(function (CompetitionSport $competitionSport) use ($horizontalPoule, $checkOnSingleQualifyRule): array {
            $calculator = $this->getSportRoundRankingCalculator($competitionSport);
            return $calculator->getItemsForHorizontalPoule($horizontalPoule, $checkOnSingleQualifyRule);
        })->toArray();
        return $this->convertSportRoundRankingsToRoundItems($horizontalPoule->getPlaces(), $sportRoundRankingItems);
    }

    /**
     * @param array<RoundRankingItem> $rankingItems
     * @param int $rank
     * @return RoundRankingItem|null
     */
    public function getItemByRank(array $rankingItems, int $rank): ?RoundRankingItem
    {
        $filtered = array_filter($rankingItems, function (RoundRankingItem $rankingItem) use ($rank): bool {
            return $rankingItem->getUniqueRank() === $rank;
        });
        return count($filtered) > 0 ? reset($filtered) : null;
    }

    /**
     * @param array<Place> $places
     * @param array<array<SportRoundRankingItem>> $sportRoundRankings
     * @return array<RoundRankingItem>
     */
    protected function convertSportRoundRankingsToRoundItems(array $places, array $sportRoundRankings): array
    {
        $map = $this->getRoundRankingItemMap($places, $sportRoundRankings);
        // TODOSPORTS CHECK IF ARRAY IS CHANGES
        $roundRankingItems = array_map(function (Place $place) use ($map): RoundRankingItem {
            return $map[$place->getRoundLocationId()];
        }, $places);
        return $this->rankItems($roundRankingItems);
    }

    /**
     * @param array<Place> $places
     * @param array<array<SportRoundRankingItem>> $sportRoundRankings
     * @return array
     */
    protected function getRoundRankingItemMap(array $places, array $sportRoundRankings): array
    {
        /** @var array<RoundRankingItem> $map */
        $map = [];
        foreach ($places as $place) {
            $map[$place->getRoundLocationId()] = new RoundRankingItem($place);
            foreach ($sportRoundRankings as $sportRoundRanking) {
                foreach ($sportRoundRanking as $sportRoundItem) {
                    $map[$sportRoundItem->getRoundLocationId()]->addSportRoundItem($sportRoundItem);
                }
            }
        }
        return $map;
    }

    /**
     * @param array<RoundRankingItem> $cumulativeRoundRankingItems
     * @return array<RoundRankingItem>
     */
    private function rankItems(array $cumulativeRoundRankingItems): array
    {
        uasort($cumulativeRoundRankingItems, function (RoundRankingItem $a, RoundRankingItem $b): int {
            if ($a->getCumulativeRank() === $b->getCumulativeRank()) {
                if ($a->getPlace()->getPouleNr() === $b->getPlace()->getPouleNr()) {
                    return $a->getPlace()->getNumber() - $b->getPlace()->getNumber();
                }
                return $a->getPlace()->getPouleNr() - $b->getPlace()->getPouleNr();
            }
            return $a->getCumulativeRank() < $b->getCumulativeRank() ? -1 : 1;
        });
        /** @var array<RoundRankingItem> $roundRankingItems */
        $roundRankingItems = [];
        $nrOfIterations = 0;
        $rank = 0;
        $previousCumulativeRank = 0;
        $cumulativeRoundRankingItem = null;
        while ($cumulativeRoundRankingItem = array_shift($cumulativeRoundRankingItems)) {
            if ($previousCumulativeRank < $cumulativeRoundRankingItem->getCumulativeRank()) {
                $rank++;
            }
            $cumulativeRoundRankingItem->setRank($rank, ++$nrOfIterations);
            $previousCumulativeRank = $cumulativeRoundRankingItem->getCumulativeRank();
            $roundRankingItems[] = $cumulativeRoundRankingItem;
        }
        return $roundRankingItems;
    }
}
