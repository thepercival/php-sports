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
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use SportsHelpers\GameMode;

class Round
{
    /**
     * @var list<int>
     */
    protected array $gameStates;

    /**
     * @param list<int>|null $gameStates
     */
    public function __construct(array $gameStates = null)
    {
        $this->gameStates = $gameStates ?? [State::Finished];
    }

    protected function getSportRoundRankingCalculator(CompetitionSport $competitionSport): SportRoundRankingCalculator
    {
        if ($competitionSport->getGameMode() === GameMode::AGAINST) {
            return new AgainstSportRoundRankingCalculator($competitionSport, $this->gameStates);
        }
        return new TogetherSportRoundRankingCalculator($competitionSport, $this->gameStates);
    }


    /**
     * @param Poule $poule
     * @return list<RoundRankingItem>
     */
    public function getItemsForPoule(Poule $poule): array
    {
        $sportRoundRankingItems = $poule->getCompetition()->getSports()->map(function (CompetitionSport $competitionSport) use ($poule): array {
            return $this->getSportRoundRankingCalculator($competitionSport)->getItemsForPoule($poule);
        })->toArray();
        return $this->convertSportRoundRankingsToRoundItems(
            array_values($poule->getPlaces()->toArray()),
            array_values($sportRoundRankingItems)
        );
    }

    /**
     * @param HorizontalPoule $horPoule
     * @return list<RoundRankingItem>
     */
    public function getItemsForHorizontalPoule(HorizontalPoule $horPoule): array
    {
        $rankingItems = [];
        foreach( $horPoule->getPlaces() as $place) {
            $pouleRannkingItems = $this->getItemsForPoule($place->getPoule());
            $pouleRankingItem = $this->getItemByRank($pouleRannkingItems, $place->getPlaceNr());
            if ($pouleRankingItem === null) {
                continue;
            }
            array_push($rankingItems, $pouleRankingItem);
        }
        return $this->rankItems($rankingItems);
    }

    /**
     * @param MultipleQualifyRule $multipleRule
     * @return list<Place>
     */
    public function getPlaceLocationsForMultipleRule(MultipleQualifyRule $multipleRule): array
    {
        return $this->getPlacesForHorizontalPoule($multipleRule->getFromHorizontalPoule());
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @return list<Place>
     */
    public function getPlacesForHorizontalPoule(HorizontalPoule $horizontalPoule): array
    {
        return array_values(array_map(function (RoundRankingItem $rankingItem): Place {
            return $rankingItem->getPlace();
        }, $this->getItemsForHorizontalPoule($horizontalPoule)));
    }

    /**
     * @param list<RoundRankingItem> $rankingItems
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
     * @param list<Place> $places
     * @param list<list<SportRoundRankingItem>> $sportRoundRankings
     * @return list<RoundRankingItem>
     */
    protected function convertSportRoundRankingsToRoundItems(array $places, array $sportRoundRankings): array
    {
        $map = $this->getRoundRankingItemMap($places, $sportRoundRankings);
        $roundRankingItems = array_map(function (Place $place) use ($map): RoundRankingItem {
            return $map[$place->getRoundLocationId()];
        }, $places);
        return $this->rankItems($roundRankingItems);
    }

    /**
     * @param array<Place> $places
     * @param list<list<SportRoundRankingItem>> $sportRoundRankings
     * @return array<string, RoundRankingItem>
     */
    protected function getRoundRankingItemMap(array $places, array $sportRoundRankings): array
    {
        $map = [];
        foreach ($places as $place) {
            $map[$place->getRoundLocationId()] = new RoundRankingItem($place);
        }
        foreach ($sportRoundRankings as $sportRoundRanking) {
            foreach ($sportRoundRanking as $sportRoundItem) {
                $map[$sportRoundItem->getPlaceLocation()->getRoundLocationId()]->addSportRoundItem($sportRoundItem);
            }
        }
        return $map;
    }

    /**
     * @param list<RoundRankingItem> $cumulativeRoundRankingItems
     * @return list<RoundRankingItem>
     */
    private function rankItems(array $cumulativeRoundRankingItems): array
    {
        usort($cumulativeRoundRankingItems, function (RoundRankingItem $a, RoundRankingItem $b): int {
            if ($a->getCumulativeRank() === $b->getCumulativeRank()) {
                $cmp = $a->compareCumulativePerformances($b);
                if ($cmp === 0.0) {
                    if ($a->getPlace()->getPouleNr() === $b->getPlace()->getPouleNr()) {
                        return $a->getPlace()->getPlaceNr() - $b->getPlace()->getPlaceNr();
                    }
                    return $a->getPlace()->getPouleNr() - $b->getPlace()->getPouleNr();
                }
                return $cmp > 0 ? 1 : -1;
            }
            return $a->getCumulativeRank() < $b->getCumulativeRank() ? -1 : 1;
        });
        /** @var list<RoundRankingItem> $roundRankingItems */
        $roundRankingItems = [];
        $nrOfIterations = 0;
        $rank = 0;
        $previousCumulative = null;
        while ($cumulativeRoundRankingItem = array_shift($cumulativeRoundRankingItems)) {
            if ($previousCumulative === null || $previousCumulative->getCumulativeRank() < $cumulativeRoundRankingItem->getCumulativeRank() ||
                $previousCumulative->getCumulativeRank() === $cumulativeRoundRankingItem->getCumulativeRank()
                && $cumulativeRoundRankingItem->compareCumulativePerformances($previousCumulative) < 0) {
                $rank++;
            }
            $cumulativeRoundRankingItem->setRank($rank, ++$nrOfIterations);
            $previousCumulative = $cumulativeRoundRankingItem;
            $roundRankingItems[] = $cumulativeRoundRankingItem;
        }
        return $roundRankingItems;
    }
}
