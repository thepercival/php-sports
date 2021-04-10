<?php

namespace Sports\Poule\Horizontal;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Place;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Round;

class Creator
{
    public function remove(Round|null ...$rounds)
    {
        foreach ($rounds as $round) {
            if ($round === null) {
                continue;
            }
            foreach ([QualifyTarget::WINNERS, QualifyTarget::LOSERS] as $target) {
                $this->removeRound($round, $target);
            }
        }
    }

    protected function removeRound(Round $round, string $target)
    {
        $horizontalPoules = $round->getHorizontalPoules($target);

        while ($horizontalPoule = $horizontalPoules->last()) {
            $horizontalPoules->removeElement($horizontalPoule);
        }
    }

    public function create(Round|null ...$rounds)
    {
        foreach ($rounds as $round) {
            if ($round === null) {
                return;
            }
            foreach ([QualifyTarget::WINNERS, QualifyTarget::LOSERS] as $target) {
                $this->createRoundHorizontalPoules($round, $target);
            }
        }
    }

    /**
     * @param Round $round
     * @param string $qualifyTarget
     * @return ArrayCollection<int|string, HorizontalPoule>
     */
    protected function createRoundHorizontalPoules(Round $round, string $qualifyTarget): ArrayCollection
    {
        $horizontalPoules = $round->getHorizontalPoules($qualifyTarget);

        $placesHorizontalOrdered = $this->getPlacesHorizontal($round);
        if ($qualifyTarget === QualifyTarget::LOSERS) {
            $placesHorizontalOrdered = array_reverse($placesHorizontalOrdered);
        }

        $nrOfPoules = $round->getPoules()->count();
        $horPlaces = array_splice($placesHorizontalOrdered, 0, $nrOfPoules);
        $previous = null;
        while (count($horPlaces) > 0) {
            $previous = new HorizontalPoule($round, $qualifyTarget, $previous, new ArrayCollection($horPlaces));
            $horPlaces = array_splice($placesHorizontalOrdered, 0, $nrOfPoules);
        }
        return $horizontalPoules;
    }

    /**
     * @param Round $round
     * @return list<Place>
     */
    protected function getPlacesHorizontal(Round $round): array
    {
        $places = [];
        foreach ($round->getPoules() as $poule) {
            $places = array_merge($places, $poule->getPlaces()->toArray());
        }
        uasort($places, function (Place $placeA, Place $placeB): int {
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
        return array_values($places);
    }
}
