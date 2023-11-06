<?php

declare(strict_types=1);

namespace Sports\Ranking\Calculator;

use Closure;
use Sports\Category;
use Sports\Game\State as GameState;
use Sports\Place;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Rule\Horizontal\Single as HorizontalSingleQualifyRule;
use Sports\Qualify\Rule\Vertical\Single as VerticalSingleQualifyRule;
use Sports\Qualify\Target;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Ranking\Calculator\Round as RoundRankingCalculator;
use Sports\Ranking\Item\End as EndRankingItem;
use Sports\Round;

class End
{
    private int $currentRank = 1;

    public function __construct(private Category $category)
    {
    }

    /**
     * @return list<EndRankingItem>
     */
    public function getItems(): array
    {
        $this->currentRank = 1;
        $getItems = function (Round $round) use (&$getItems): array {
            /** @var Closure(Round):list<EndRankingItem> $getItems */
            $items = [];
            foreach ($round->getTargetQualifyGroups(QualifyTarget::Winners) as $qualifyGroup) {
                $items = array_merge($items, $getItems($qualifyGroup->getChildRound()));
            }
            if ($round->getGamesState() === GameState::Finished) {
                $items = array_merge($items, $this->getDropouts($round));
            } else {
                $items = array_merge($items, $this->getDropoutsNotPlayed($round));
            }
            foreach (array_reverse($round->getTargetQualifyGroups(QualifyTarget::Losers)->slice(0)) as $qualifyGroup) {
                $items = array_merge($items, $getItems($qualifyGroup->getChildRound()));
            }
            return $items;
        };
        return $getItems($this->category->getRootRound());
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
            // foreach ([QualifyTarget::Winners, QualifyTarget::Losers] as $qualifyTarget) {
            foreach ($round->getHorizontalPoules(QualifyTarget::Winners) as $horPoule) {
                $horPouleDropouts = $this->getHorizontalPouleDropouts($horPoule, $nrOfDropouts);
                $dropouts = array_merge( $dropouts, $horPouleDropouts );
                if ($nrOfDropouts === 0) {
                    break;
                }
            }
        }
        return $dropouts;
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @param int $nrOfDropouts
     * @return list<EndRankingItem>
     */
    protected function getHorizontalPouleDropouts(HorizontalPoule $horizontalPoule, int &$nrOfDropouts): array
    {
        $dropOutPlaces = [];
        $roundRankingCalculator = new RoundRankingCalculator();
        $rankedPlaces = $roundRankingCalculator->getPlacesForHorizontalPoule($horizontalPoule);
        $nrOfQualifiers = $this->getHorizontalPouleNrOfQualifiers($horizontalPoule);
        array_splice($rankedPlaces, 0, $nrOfQualifiers);
        while( $nrOfDropouts > 0 && count($rankedPlaces) > 0) {
            $dropOutPlaces[] = array_shift($rankedPlaces);
            $nrOfDropouts--;
        }
        return array_map(function (Place $dropOutPlace): EndRankingItem {
            return new EndRankingItem($this->currentRank, $this->currentRank++, $dropOutPlace->getStartLocation());
        }, $dropOutPlaces);
    }

    public function getHorizontalPouleNrOfQualifiers(HorizontalPoule $horizontalPoule): int
    {
        $qualifyRule = $horizontalPoule->getQualifyRuleNew();
        if ($qualifyRule === null) {
            return 0;
        }
        if ($qualifyRule instanceof HorizontalSingleQualifyRule || $qualifyRule instanceof VerticalSingleQualifyRule) {
            return count($qualifyRule->getMappings());
        }
        return $qualifyRule->getNrOfToPlaces();
    }
}
