<?php
declare(strict_types=1);

namespace Sports\Qualify\Rule;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Poule;
use Sports\Place;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\OriginCalculator as QualifyOriginCalculator;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use Sports\Qualify\PlaceMapping as QualifyPlaceMapping;
use Sports\Round;

class DefaultCreator
{
    private QualifyOriginCalculator $qualifyOriginCalculator;

    public function __construct()
    {
        $this->qualifyOriginCalculator = new QualifyOriginCalculator();
    }

    /**
     * @param list<HorizontalPoule> $fromHorPoules
     * @param QualifyGroup $qualifyGroup
     */
    public function createRules(array $fromHorPoules, QualifyGroup $qualifyGroup)
    {
        $childRoundPlaces = $this->getChildRoundPlaces($qualifyGroup);
        $fromHorPoule = array_shift($fromHorPoules);
        $previousRule = null;
        while ($fromHorPoule !== null && count($childRoundPlaces) > 0) {
            $toPlaces = array_splice($childRoundPlaces, 0, count($fromHorPoule->getPlaces()));
            if ($fromHorPoule->getPlaces()->count() > count($toPlaces)) {
                new MultipleQualifyRule($fromHorPoule, $qualifyGroup, $toPlaces);
            } else {
                $placeMappings = $this->createPlaceMappings($fromHorPoule, $toPlaces);
                $previousRule = new SingleQualifyRule($fromHorPoule, $qualifyGroup, $placeMappings, $previousRule);
            }
            $fromHorPoule = array_shift($fromHorPoules);
        }
    }

    /**
     * @param QualifyGroup $qualifyGroup
     * @return list<Place>
     */
    protected function getChildRoundPlaces(QualifyGroup $qualifyGroup): array
    {
        $childRoundPlaces = $qualifyGroup->getChildRound()->getPlaces(Round::ORDER_NUMBER_POULE);
        if ($qualifyGroup->getTarget() === QualifyTarget::WINNERS) {
            return $childRoundPlaces;
        }
        return array_reverse($childRoundPlaces);
    }

    /**
     * @param HorizontalPoule $fromHorPoule
     * @param list<Place> $childRoundPlaces
     * @return ArrayCollection<int|string, QualifyPlaceMapping>
     */
    public function createPlaceMappings(HorizontalPoule $fromHorPoule, array $childRoundPlaces): ArrayCollection
    {
        $mappings = new ArrayCollection();
        $fromHorPoulePlaces = $fromHorPoule->getPlaces()->slice(0);
        while ($childRoundPlace = array_shift($childRoundPlaces)) {
            $fromHorPoulePlace = $this->getBestPick($childRoundPlace, $fromHorPoulePlaces);
            $idx = array_search($fromHorPoulePlace, $fromHorPoulePlaces, true);
            if ($idx === false) {
                continue;
            }
            array_splice($fromHorPoulePlaces, $idx,1);
            $mappings->add(new QualifyPlaceMapping($fromHorPoulePlace, $childRoundPlace));
        }
        return $mappings;
    }

    /**
     * @param Place $childRoundPlace
     * @param list<Place> $fromHorPoulePlaces
     * @return Place
     */
    protected function getBestPick(Place $childRoundPlace, array $fromHorPoulePlaces): Place
    {
        $fromHorPoulePlacesWithFewestPouleOrigins = $this->getFewestOverlappingPouleOrigins(
            $childRoundPlace->getPoule(),
            $fromHorPoulePlaces
        );
        if (count($fromHorPoulePlacesWithFewestPouleOrigins) === 1) {
            return reset($fromHorPoulePlacesWithFewestPouleOrigins);
        }
        $otherChildRoundPoules = $this->getOtherChildRoundPoules($childRoundPlace->getPoule());
        $fromHorPoulePlacesWithMostOtherPouleOrigins = $this->getMostOtherOverlappingPouleOrigins(
            $otherChildRoundPoules,
            $fromHorPoulePlacesWithFewestPouleOrigins
        );
        return reset($fromHorPoulePlacesWithMostOtherPouleOrigins);
    }

    /**
     * @param Poule $toPoule
     * @param list<Place> $fromHorPoulePlaces
     * @return list<Place>
     */
    protected function getFewestOverlappingPouleOrigins(Poule $toPoule, array $fromHorPoulePlaces): array
    {
        $bestFromPlaces = [];
        $fewestOverlappingOrigins = null;
        foreach ($fromHorPoulePlaces as $fromHorPoulePlace) {
            $nrOfOverlappingOrigins = $this->getPossibleOverlapses($fromHorPoulePlace->getPoule(), [$toPoule]);
            if ($fewestOverlappingOrigins === null || $nrOfOverlappingOrigins < $fewestOverlappingOrigins) {
                $bestFromPlaces = [$fromHorPoulePlace];
                $fewestOverlappingOrigins = $nrOfOverlappingOrigins;
            } elseif ($fewestOverlappingOrigins === $nrOfOverlappingOrigins) {
                array_push($bestFromPlaces, $fromHorPoulePlace);
            }
        }
        return $bestFromPlaces;
    }

    /**
     * @param Poule $poule
     * @return list<Poule>
     */
    protected function getOtherChildRoundPoules(Poule $poule): array
    {
        return array_values($poule->getRound()->getPoules()->filter(function (Poule $pouleIt) use ($poule): bool {
            return $pouleIt !== $poule;
        })->toArray());
    }

    /**
     * @param list<Poule> $otherChildRoundPoules
     * @param list<Place> $fromHorPoulePlaces
     * @return list<Place>
     */
    protected function getMostOtherOverlappingPouleOrigins(
        array $otherChildRoundPoules,
        array $fromHorPoulePlaces
    ): array
    {
        $bestFromPlaces = [];
        $mostOverlappingOrigins = null;
        foreach ($fromHorPoulePlaces as $fromHorPoulePlace) {
            $nrOfOverlappingOrigins = $this->getPossibleOverlapses($fromHorPoulePlace->getPoule(), $otherChildRoundPoules);
            if ($mostOverlappingOrigins === null || $nrOfOverlappingOrigins > $mostOverlappingOrigins) {
                $bestFromPlaces = [$fromHorPoulePlace];
                $mostOverlappingOrigins = $nrOfOverlappingOrigins;
            } elseif ($mostOverlappingOrigins === $nrOfOverlappingOrigins) {
                array_push($bestFromPlaces, $fromHorPoulePlace);
            }
        }
        return $bestFromPlaces;
    }

    /**
     * @param Poule $fromPoule
     * @param list<Poule> $toPoules
     * @return int
     */
    protected function getPossibleOverlapses(Poule $fromPoule, array $toPoules): int
    {
        $nrOfOverlapses = 0;
        foreach ($toPoules as $toPoule) {
            $nrOfOverlapses += $this->qualifyOriginCalculator->getPossibleOverlapses($fromPoule, $toPoule);
        }
        return $nrOfOverlapses;
    }
}
