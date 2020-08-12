<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 5-6-2019
 * Time: 07:58
 */

namespace Sports\Poule\Horizontal;

use Sports\Qualify\Group as QualifyGroup;
use Sports\Round;
use Sports\Place;
use Sports\Poule\Horizontal as HorizontalPoule;

class Service
{
    /**
     * @var Round
     */
    private $round;
    /**
     * @var array | int[]
     */
    private $winnersAndLosers;

    public function __construct(Round $round, int $winnersOrLosers = null)
    {
        $this->round = $round;

        if ($winnersOrLosers === null) {
            $this->winnersAndLosers = [QualifyGroup::WINNERS, QualifyGroup::LOSERS];
        } else {
            $this->winnersAndLosers = [$winnersOrLosers];
        }
    }

    public function recreate()
    {
        $this->remove();
        $this->create();
    }

    protected function remove()
    {
        foreach ($this->winnersAndLosers as $winnersOrLosers) {
            $horizontalPoules = &$this->round->getHorizontalPoules($winnersOrLosers);
            while (count($horizontalPoules) > 0) {
                $horizontalPoule = array_pop($horizontalPoules);

                $places = &$horizontalPoule->getPlaces();
                while (count($places) > 0) {
                    $place = array_pop($places);
                    $place->setHorizontalPoule($winnersOrLosers, null);
                }
            }
        }
    }

    protected function create()
    {
        foreach ($this->winnersAndLosers as $winnersOrLosers) {
            $this->createRoundHorizontalPoules($winnersOrLosers);
        }
    }

    /**
     * @param int $winnersOrLosers
     * @return array | HorizontalPoule[]
     */
    protected function createRoundHorizontalPoules(int $winnersOrLosers): array
    {
        $horizontalPoules = &$this->round->getHorizontalPoules($winnersOrLosers);

        $placesOrderedByPlaceNumber = $this->getPlacesHorizontal();
        if ($winnersOrLosers === QualifyGroup::LOSERS) {
            $placesOrderedByPlaceNumber = array_reverse($placesOrderedByPlaceNumber);
        }

        foreach ($placesOrderedByPlaceNumber as $placeIt) {
            $filteredHorizontalPoules = array_filter($horizontalPoules, function ($horizontalPoule) use ($placeIt,$winnersOrLosers): bool {
                foreach ($horizontalPoule->getPlaces() as $poulePlaceIt) {
                    $poulePlaceNrIt = $poulePlaceIt->getNumber();
                    if ($winnersOrLosers === QualifyGroup::LOSERS) {
                        $poulePlaceNrIt = ($poulePlaceIt->getPoule()->getPlaces()->count() + 1) - $poulePlaceNrIt;
                    }
                    $placeNrIt = $placeIt->getNumber();
                    if ($winnersOrLosers === QualifyGroup::LOSERS) {
                        $placeNrIt = ($placeIt->getPoule()->getPlaces()->count() + 1) - $placeNrIt;
                    }
                    if ($poulePlaceNrIt === $placeNrIt) {
                        return true;
                    }
                }
                return false;
            });

            $foundHorizontalPoule = array_shift($filteredHorizontalPoules);
            if ($foundHorizontalPoule === null) {
                $foundHorizontalPoule = new HorizontalPoule($this->round, count($horizontalPoules) + 1);
                $horizontalPoules[] = $foundHorizontalPoule;
            }
            $placeIt->setHorizontalPoule($winnersOrLosers, $foundHorizontalPoule);
        }
        return $horizontalPoules;
    }

    /**
     * @return array | Place[]
     */
    protected function getPlacesHorizontal(): array
    {
        $places = [];
        foreach ($this->round->getPoules() as $poule) {
            $places = array_merge($places, $poule->getPlaces()->toArray());
        }
        uasort($places, function ($placeA, $placeB) {
            if ($placeA->getNumber() > $placeB->getNumber()) {
                return 1;
            }
            if ($placeA->getNumber() < $placeB->getNumber()) {
                return -1;
            }
            if ($placeA->getPoule()->getNumber() > $placeB->getPoule()->getNumber()) {
                return 1;
            }
            if ($placeA->getPoule()->getNumber() < $placeB->getPoule()->getNumber()) {
                return -1;
            }
            return 0;
        });
        return $places;
    }

    /**
     * @param array $roundHorizontalPoules | HorizontolPoule[]
     * @param array $horizontalPoulesCreators | HorizontolPoulesCreator[]
     */
    public function updateQualifyGroups(
        array $roundHorizontalPoules,
        array $horizontalPoulesCreators
    ) {
        foreach ($horizontalPoulesCreators as $creator) {
            $horizontalPoules = &$creator->qualifyGroup->getHorizontalPoules();
            $horizontalPoules = [];
            $qualifiersAdded = 0;
            while ($qualifiersAdded < $creator->nrOfQualifiers) {
                $roundHorizontalPoule = array_shift($roundHorizontalPoules);
                $roundHorizontalPoule->setQualifyGroup($creator->qualifyGroup);
                $qualifiersAdded += count($roundHorizontalPoule->getPlaces());
            }
        }
        foreach ($roundHorizontalPoules as $roundHorizontalPoule) {
            $roundHorizontalPoule->setQualifyGroup(null);
        }
    }
}
