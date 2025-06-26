<?php

declare(strict_types=1);

namespace Sports\Ranking\Calculator;

use Sports\Competition\CompetitionSport as CompetitionSport;
use Sports\Game\GameState as GameState;
use Sports\Place;
use Sports\Poule;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Rule\Horizontal\Multiple as HorizontalMultipleQualifyRule;
use Sports\Qualify\Rule\Vertical\Multiple as VerticalMultipleQualifyRule;
use Sports\Qualify\Rule\Vertical\Single as VerticalSingleQualifyRule;
use Sports\Ranking\Calculator\Round\Sport as SportRoundRankingCalculator;
use Sports\Ranking\Calculator\Round\Sport\Against as AgainstSportRoundRankingCalculator;
use Sports\Ranking\Calculator\Round\Sport\Together as TogetherSportRoundRankingCalculator;
use Sports\Ranking\Item\Round as RoundRankingItem;
use Sports\Ranking\Item\Round\Sport as SportRoundRankingItem;
use SportsHelpers\GameMode;

class Round
{
    /**
     * @var list<GameState>
     */
    protected array $gameStates;

    /**
     * @param list<GameState>|null $gameStates
     */
    public function __construct(
        array $gameStates = null,
        protected Cumulative $cumulative = Cumulative::ByRank
    ) {
        $this->gameStates = $gameStates ?? [GameState::Finished];
    }

    protected function getSportRoundRankingCalculator(CompetitionSport $competitionSport): SportRoundRankingCalculator
    {
        if ($competitionSport->getGameMode() === GameMode::Against) {
            return new AgainstSportRoundRankingCalculator($competitionSport, $this->gameStates);
        }
        return new TogetherSportRoundRankingCalculator($competitionSport, $this->gameStates);
    }

    /**
     * @param Poule $poule
     * @return list<Place>
     */
    public function getPlacesForPoule(Poule $poule): array
    {
        return array_map(function (RoundRankingItem $rankingItem): Place {
            return $rankingItem->getPlace();
        }, $this->getItemsForPoule($poule));
    }

    /**
     * @param Poule $poule
     * @return list<RoundRankingItem>
     */
    public function getItemsForPoule(Poule $poule): array
    {
        $sportRoundRankingItems = array_map(function (CompetitionSport $competitionSport) use ($poule): array {
            return $this->getSportRoundRankingCalculator($competitionSport)->getItemsForPoule($poule);
        }, $poule->getCompetition()->getSports()->toArray());
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
        foreach ($horPoule->getPlaces() as $place) {
            $pouleRannkingItems = $this->getItemsForPoule($place->getPoule());
            $rank = $place->getPlaceNr();
            $pouleRankingItem = $this->getItemByRank($pouleRannkingItems, $rank);
            if ($pouleRankingItem === null) {
                continue;
            }
            array_push($rankingItems, $pouleRankingItem);
        }
        return $this->rankItems($rankingItems);
    }

    /**
     * @param HorizontalMultipleQualifyRule | VerticalMultipleQualifyRule | VerticalSingleQualifyRule $rankedRule
     * @return list<Place>
     */
    public function getPlaceLocationsForRankedRule(HorizontalMultipleQualifyRule | VerticalMultipleQualifyRule | VerticalSingleQualifyRule $rankedRule): array
    {
        return $this->getPlacesForHorizontalPoule($rankedRule->getFromHorizontalPoule());
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @return list<Place>
     */
    public function getPlacesForHorizontalPoule(HorizontalPoule $horizontalPoule): array
    {
        return array_map(function (RoundRankingItem $rankingItem): Place {
            return $rankingItem->getPlace();
        }, $this->getItemsForHorizontalPoule($horizontalPoule));
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
            return $map[$place->getUniqueIndex()];
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
            $map[$place->getUniqueIndex()] = new RoundRankingItem($place);
        }
        foreach ($sportRoundRankings as $sportRoundRanking) {
            foreach ($sportRoundRanking as $sportRoundItem) {
                $map[$sportRoundItem->getPlaceLocation()->getUniqueIndex()]->addSportRoundItem($sportRoundItem);
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
            return $this->compareBy($a, $b,);
        });
        /** @var list<RoundRankingItem> $roundRankingItems */
        $roundRankingItems = [];
        $nrOfIterations = 0;
        $rank = 0;
        $previousCumulative = null;
        while ($cumulativeRoundRankingItem = array_shift($cumulativeRoundRankingItems)) {
            if ($this->differs($cumulativeRoundRankingItem, $previousCumulative)) {
                $rank++;
            }
            $cumulativeRoundRankingItem->setRank($rank, ++$nrOfIterations);
            $previousCumulative = $cumulativeRoundRankingItem;
            $roundRankingItems[] = $cumulativeRoundRankingItem;
        }
        return $roundRankingItems;
    }

    protected function compareBy(RoundRankingItem $a, RoundRankingItem $b): int
    {
        if ($this->cumulative === Cumulative::ByRank
            && $a->getCumulativeRank() !== $b->getCumulativeRank()) {
            return $a->getCumulativeRank() < $b->getCumulativeRank() ? -1 : 1;
        }
        $cmp = $a->compareCumulativePerformances($b);
        if ($cmp !== 0.0) {
            return $cmp > 0 ? 1 : -1;
        }
        if ($a->getPlace()->getPouleNr() === $b->getPlace()->getPouleNr()) {
            return $a->getPlace()->getPlaceNr() - $b->getPlace()->getPlaceNr();
        }
        return $a->getPlace()->getPouleNr() - $b->getPlace()->getPouleNr();
    }

    protected function differs(RoundRankingItem $a, RoundRankingItem|null $b): bool
    {
        if ($b === null) {
            return true;
        }
        if ($this->cumulative === Cumulative::ByRank && $a->getCumulativeRank() !== $b->getCumulativeRank()) {
            return true;
        }
        return $a->compareCumulativePerformances($b) !== 0.0;
    }
}
