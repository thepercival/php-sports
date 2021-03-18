<?php

namespace Sports\Ranking\Calculator;

use Sports\State;
use Sports\Place;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Round;
use Sports\Structure;
use Sports\Ranking\Item\End as EndRankingItem;
use Sports\Ranking\Calculator\Round as RoundRankingCalculator;

class End
{
    private int $currentRank = 1;

    public function __construct(private Structure $structure, private int $ruleSet)
    {
    }

    /**
     * @return list<EndRankingItem>
     */
    public function getItems(): array
    {
        $this->currentRank = 1;
        $getItems = function (Round $round) use (&$getItems) : array {
            $items = [];
            foreach ($round->getQualifyGroups(QualifyGroup::WINNERS) as $qualifyGroup) {
                $items = array_merge($items, $getItems($qualifyGroup->getChildRound()));
            }
            if ($round->getState() === State::Finished) {
                $items = array_merge($items, $this->getDropouts($round));
            } else {
                $items = array_merge($items, $this->getDropoutsNotPlayed($round));
            }
            foreach (array_reverse($round->getQualifyGroups(QualifyGroup::LOSERS)->slice(0)) as $qualifyGroup) {
                $items = array_merge($items, $getItems($qualifyGroup->getChildRound()));
            }
            return $items;
        };
        return array_values($getItems($this->structure->getRootRound()));
    }

    /**
     * @param Round $round
     * @return list<EndRankingItem>
     */
    protected function getDropoutsNotPlayed(Round $round): array
    {
        $items = [];
        $nrOfDropouts = $round->getNrOfPlaces() - $round->getNrOfPlacesChildren();
        for ($i = 0; $i < $nrOfDropouts; $i++) {
            $items[] = new EndRankingItem($this->currentRank, $this->currentRank++, null);
        }
        return $items;
    }

    /**
     * @param Round $round
     * @return list<EndRankingItem>
     */
    protected function getDropouts(Round $round): array
    {
        $dropouts = [];
        $nrOfDropouts = $round->getNrOfDropoutPlaces();
        while ($nrOfDropouts > 0) {
            foreach ([QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers) {
                foreach ($round->getHorizontalPoules($winnersOrLosers) as $horizontalPoule) {
                    /** @var HorizontalPoule $horizontalPoule */
                    if ($horizontalPoule->getQualifyGroup() !== null && $horizontalPoule->getQualifyGroup()->getNrOfToPlacesTooMuch() === 0) {
                        if ($nrOfDropouts > 0) {
                            continue;
                        }
                        break;
                    }
                    $dropoutsHorizontalPoule = $this->getDropoutsHorizontalPoule($horizontalPoule);
                    while (($nrOfDropouts - count($dropoutsHorizontalPoule)) < 0) {
                        array_pop($dropoutsHorizontalPoule);
                    }
                    $dropouts = array_merge($dropouts, $dropoutsHorizontalPoule);
                    $nrOfDropouts -= count($dropoutsHorizontalPoule);
                    if ($nrOfDropouts === 0) {
                        break;
                    }
                }
                if ($nrOfDropouts === 0) {
                    break;
                }
            }
        }
        return $dropouts;
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @return list<EndRankingItem>
     */
    protected function getDropoutsHorizontalPoule(HorizontalPoule $horizontalPoule): array
    {
        $roundRankingCalculator = new RoundRankingCalculator();
        $rankedPlaces = $roundRankingCalculator->getPlacesForHorizontalPoule($horizontalPoule);
        array_splice($rankedPlaces, 0, $horizontalPoule->getNrOfQualifiers());
        return array_map(function (Place $rankedPlace): EndRankingItem {
            return new EndRankingItem($this->currentRank, $this->currentRank++, $rankedPlace->getStartLocation());
        }, $rankedPlaces);
    }
}
