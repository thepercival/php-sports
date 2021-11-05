<?php

declare(strict_types=1);

namespace Sports\Qualify\Rule;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use Sports\Place;
use Sports\Poule;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\FromPoulePicker;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\PlaceMapping as QualifyPlaceMapping;
use Sports\Qualify\PossibleFromMap;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Round;

class DefaultCreator
{
    private PossibleFromMap $possibleFromMap;

    public function __construct(Round $leafRound)
    {
        $this->possibleFromMap = new PossibleFromMap($leafRound);
    }

    /**
     * @param list<HorizontalPoule> $fromHorPoules
     * @param QualifyGroup $qualifyGroup
     */
    public function createRules(array $fromHorPoules, QualifyGroup $qualifyGroup): void
    {
        $childRoundPlaces = $this->getChildRoundPlacesLikeSnake($qualifyGroup);
        $fromHorPoule = array_shift($fromHorPoules);
        $previousRule = null;
        $toPlaces = null;
        while ($fromHorPoule !== null && count($childRoundPlaces) >= $fromHorPoule->getPlaces()->count()) {
            $nrOfFromPlaces = $fromHorPoule->getPlaces()->count();
            /** @var list<Place> $toPlaces */
            $toPlaces = array_values(array_splice($childRoundPlaces, 0, $nrOfFromPlaces));
            $placeMappings = $this->createQualifyPlaceMappings($fromHorPoule, $toPlaces);
            $previousRule = new SingleQualifyRule($fromHorPoule, $qualifyGroup, $placeMappings, $previousRule);
            $fromHorPoule = array_shift($fromHorPoules);
        }
        if ($fromHorPoule !== null && count($childRoundPlaces) > 0 && $toPlaces !== null) {
            /*$rule = */new MultipleQualifyRule($fromHorPoule, $qualifyGroup, $childRoundPlaces);
            /*$this->possibleFromMap->addRule($rule);*/
        }
    }

    /**
     * @param QualifyGroup $qualifyGroup
     * @return list<Place>
     */
    protected function getChildRoundPlacesLikeSnake(QualifyGroup $qualifyGroup): array
    {
        $horPoules = $qualifyGroup->getChildRound()->getHorizontalPoules($qualifyGroup->getTarget());
        $places = [];
        $reverse = false;
        foreach ($horPoules as $horPoule) {
            $horPoulePlaces = $horPoule->getPlaces()->toArray();
            $horPoulePlace = $reverse ? array_pop($horPoulePlaces) : array_shift($horPoulePlaces);
            while ($horPoulePlace !== null) {
                array_push($places, $horPoulePlace);
                $horPoulePlace = $reverse ? array_pop($horPoulePlaces) : array_shift($horPoulePlaces);
            }
            $reverse = !$reverse;
        }
        return $places;
    }

    /**
     * @param HorizontalPoule $fromHorPoule
     * @param list<Place> $childRoundPlaces
     * @return Collection<int, QualifyPlaceMapping>
     */
    public function createQualifyPlaceMappings(
        HorizontalPoule $fromHorPoule,
        array $childRoundPlaces
    ): Collection {
        $fromPoulePicker = new FromPoulePicker($this->possibleFromMap);
        /** @var Collection<int, QualifyPlaceMapping> $mappings */
        $mappings = new ArrayCollection();
        $fromHorizontalPlaces = array_values($fromHorPoule->getPlaces()->slice(0));
        while ($childRoundPlace = array_shift($childRoundPlaces)) {
            $bestFromPoule = $fromPoulePicker->getBestFromPoule(
                $childRoundPlace->getPoule(),
                array_values(array_map(fn (Place $place) => $place->getPoule(), $fromHorizontalPlaces)),
                array_values(array_map(fn (Place $place) => $place->getPoule(), $childRoundPlaces))
            );
            $bestFromPlace = $this->removeBestHorizontalPlace($fromHorizontalPlaces, $bestFromPoule);
            $placeMapping = new QualifyPlaceMapping($bestFromPlace, $childRoundPlace);
            $mappings->add($placeMapping);
            $this->possibleFromMap->addPlaceMapping($placeMapping);
        }
        return $mappings;
    }

    /**
     * @param list<Place> $fromHorizontalPlaces
     * @param Poule $bestFromPoule
     * @throws Exception
     */
    protected function removeBestHorizontalPlace(array &$fromHorizontalPlaces, Poule $bestFromPoule): Place
    {
        $bestPouleNr = $bestFromPoule->getNumber();
        $fromPlaces = array_filter($fromHorizontalPlaces, fn ($place) => $place->getPouleNr() === $bestPouleNr);
        $bestFromPlace = reset($fromPlaces);
        if ($bestFromPlace === false) {
            throw new Exception('fromPlace should be found', E_ERROR);
        }
        $idx = array_search($bestFromPlace, $fromHorizontalPlaces, true);
        if ($idx === false) {
            throw new Exception('fromPlace should be found', E_ERROR);
        }
        array_splice($fromHorizontalPlaces, $idx, 1);
        return $bestFromPlace;
    }
}
