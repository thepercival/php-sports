<?php

declare(strict_types=1);

namespace Sports\Ranking;

use Sports\Place\Location as PlaceLocation;
use Sports\Poule;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Place;
use Sports\Round;
use Sports\Ranking\RoundItem\Ranked as RankedRoundItem;
use SportsHelpers\GameMode;

class Service
{
    /**
     * @var Service\Against | Service\Together
     */
    private $helper;

    const MostPoints = 1;
    const FewestGames = 2;
    const BestAgainstEachOther = 3;
    const BestUnitDifference = 4;
    const BestSubUnitDifference = 5;
    const MostUnitsScored = 6;
    const MostSubUnitsScored = 7;

    public function __construct(Round $round, int $rulesSet, int $gameStates = null)
    {
        $gameMode = $round->getNumber()->getValidPlanningConfig()->getGameMode();
        if( $gameMode === GameMode::AGAINST ) {
            $this->helper = new Service\Against($round, $rulesSet, $gameStates );
        }
    }

    public function getRuleDescriptions()
    {
        return $this->helper->getRuleDescriptions();
    }

    /**
     * @param Poule $poule
     * @return array | RankedRoundItem[]
     */
    public function getItemsForPoule(Poule $poule): array
    {
        return $this->helper->getItemsForPoule($poule);
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @return array | PlaceLocation[]
     */
    public function getPlaceLocationsForHorizontalPoule(HorizontalPoule $horizontalPoule): array
    {
        return $this->helper->getPlaceLocationsForHorizontalPoule($horizontalPoule);
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @return array | Place[]
     */
    public function getPlacesForHorizontalPoule(HorizontalPoule $horizontalPoule): array
    {
        return $this->helper->getPlacesForHorizontalPoule($horizontalPoule);
    }

    /**
     * @param HorizontalPoule $horizontalPoule
     * @param bool|null $checkOnSingleQualifyRule
     * @return array | RankedRoundItem[]
     */
    public function getItemsForHorizontalPoule(HorizontalPoule $horizontalPoule, bool $checkOnSingleQualifyRule = null): array
    {
        return $this->helper->getItemsForHorizontalPoule($horizontalPoule, $checkOnSingleQualifyRule);
    }

    /**
     * @param array $rankingItems | RankedRoundItem[]
     * @param int $rank
     * @return RankedRoundItem
     */
    public function getItemByRank(array $rankingItems, int $rank): RankedRoundItem
    {
        return $this->helper->getItemByRank($rankingItems, $rank);
    }
}
