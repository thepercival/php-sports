<?php
declare(strict_types=1);

namespace Sports\Poule\Horizontal;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Place;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Round;

class Creator
{
    public function remove(Round|null ...$rounds): void
    {
        foreach ($rounds as $round) {
            if ($round === null) {
                continue;
            }
            foreach ([QualifyTarget::Winners, QualifyTarget::Losers] as $target) {
                $this->removeRound($round, $target);
            }
        }
    }

    protected function removeRound(Round $round, QualifyTarget $target): void
    {
        $horizontalPoules = $round->getHorizontalPoules($target);

        while ($horizontalPoule = $horizontalPoules->last()) {
            $horizontalPoules->removeElement($horizontalPoule);
        }
    }

    public function create(Round|null ...$rounds): void
    {
        foreach ($rounds as $round) {
            if ($round === null) {
                return;
            }
            foreach ([QualifyTarget::Winners, QualifyTarget::Losers] as $target) {
                $this->createRoundHorizontalPoules($round, $target);
            }
        }
    }

    /**
     * @param Round $round
     * @param QualifyTarget $qualifyTarget
     * @return Collection<int|string, HorizontalPoule>
     */
    protected function createRoundHorizontalPoules(Round $round, QualifyTarget $qualifyTarget): Collection
    {
        $horizontalPoules = $round->getHorizontalPoules($qualifyTarget);

        $placesHorizontalOrdered = $this->getPlacesHorizontal($round);
        if ($qualifyTarget === QualifyTarget::Losers) {
            $placesHorizontalOrdered = array_reverse($placesHorizontalOrdered);
        }

        $nrOfPoules = $round->getPoules()->count();
        $horPlaces = array_values(array_splice($placesHorizontalOrdered, 0, $nrOfPoules));
        $previous = null;
        while (count($horPlaces) > 0) {
            /** @var Collection<int|string, Place> $horPlacesCollection */
            $horPlacesCollection = new ArrayCollection($horPlaces);
            $previous = new HorizontalPoule($round, $qualifyTarget, $previous, $horPlacesCollection);
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
            if($placeA->getPlaceNr() === $placeB->getPlaceNr()) {
                return $placeA->getPouleNr() - $placeB->getPouleNr();
            }
            return $placeA->getPlaceNr() - $placeB->getPlaceNr();
//            if ($placeA->getPlaceNr() > $placeB->getPlaceNr()) {
//                return 1;
//            }
//            if ($placeA->getPlaceNr() < $placeB->getPlaceNr()) {
//                return -1;
//            }
//            if ($placeA->getPouleNr() > $placeB->getPouleNr()) {
//                return 1;
//            }
//            if ($placeA->getPouleNr() < $placeB->getPouleNr()) {
//                return -1;
//            }
//            return 0;
        });
        return array_values($places);
    }
}
