<?php

declare(strict_types=1);

namespace Sports\Qualify\Rule\Creator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use Sports\Place;
use Sports\Poule;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\FromPoulePicker;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Mapping\ByPlace as QualifyByPlaceMapping;
use Sports\Qualify\PossibleFromMap;
use Sports\Qualify\Rule\Horizontal\Multiple as HorizontalMultipleQualifyRule;
use Sports\Qualify\Rule\Horizontal\Single as HorizontalSingleQualifyRule;
use Sports\Round;

class Horizontal
{
    private PossibleFromMap $possibleFromMap;

    public function __construct(Round $leafRound)
    {
        $this->possibleFromMap = new PossibleFromMap($leafRound);
    }

    /**
     * @param list<HorizontalPoule> $fromRoundHorPoules
     * @param QualifyGroup $qualifyGroup
     * @return list<HorizontalPoule>
     */
    public function createRules(array $fromRoundHorPoules, QualifyGroup $qualifyGroup): array
    {
//        $childRoundPlaces = $this->getChildRoundPlacesLikeSnake($qualifyGroup);
//        $fromHorPoule = array_shift($fromHorPoules);
//        $previousRule = null;
//        while ($fromHorPoule !== null && count($childRoundPlaces) >= $fromHorPoule->getPlaces()->count()) {
//            $nrOfFromPlaces = $fromHorPoule->getPlaces()->count();
//            /** @var Place $toPlaces */
//            $toPlaces = array_splice($childRoundPlaces, 0, $nrOfFromPlaces);
//            $placeMappings = $this->createQualifyPlaceMappings($fromHorPoule, $toPlaces);
//            $previousRule = new SingleQualifyRule($fromHorPoule, $qualifyGroup, $placeMappings, $previousRule);
//            $fromHorPoule = array_shift($fromHorPoules);
//        }
//        if ($fromHorPoule !== null && count($childRoundPlaces) > 0) {
//            new MultipleQualifyRule($fromHorPoule, $qualifyGroup, $childRoundPlaces);
//        }
        $childRound = $qualifyGroup->getChildRound();
        $nrOfChildRoundPlaces = $childRound->getNrOfPlaces();
        $fromHorPoules = [];
        while ($nrOfChildRoundPlaces > 0) {

            $fromRoundHorPoule = array_shift($fromRoundHorPoules);
            if ($fromRoundHorPoule === null) {
                throw new Exception('fromRoundHorPoule should not be null', E_ERROR);
            }
            $fromHorPoules[] = $fromRoundHorPoule;
            $nrOfChildRoundPlaces -= count($fromRoundHorPoule->getPlaces());

        }
        $this->createRulesFromHorPoules($fromHorPoules, $qualifyGroup);
        return $fromRoundHorPoules;
    }

    /**
     * @param list<HorizontalPoule> $fromHorPoules
     * @param QualifyGroup $qualifyGroup
     */
    protected function createRulesFromHorPoules(array $fromHorPoules, QualifyGroup $qualifyGroup): void {
        $childRoundPlaces = $this->getChildRoundPlacesLikeSnake($qualifyGroup);
        $fromHorPoule = array_shift($fromHorPoules);
        $previousRule = null;
        while ( $fromHorPoule !== null && count($childRoundPlaces) >= count($fromHorPoule->getPlaces()) ) {
            /** @var list<Place> $toPlaces */
            $toPlaces = array_splice( $childRoundPlaces, 0, count($fromHorPoule->getPlaces()));
            $placeMappings = $this->createByPlaceMappings($fromHorPoule, $toPlaces );
            $previousRule = new HorizontalSingleQualifyRule($fromHorPoule, $qualifyGroup, $placeMappings, $previousRule);
            $fromHorPoule = array_shift($fromHorPoules);
        }
        if ($fromHorPoule !== null && count($childRoundPlaces) > 0) {
            new HorizontalMultipleQualifyRule($fromHorPoule, $qualifyGroup, $childRoundPlaces);
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
     * @return Collection<int, QualifyByPlaceMapping>
     */
    public function createByPlaceMappings(
        HorizontalPoule $fromHorPoule,
        array $childRoundPlaces
    ): Collection {
        $fromPoulePicker = new FromPoulePicker($this->possibleFromMap);
        /** @var Collection<int, QualifyByPlaceMapping> $mappings */
        $mappings = new ArrayCollection();
        $fromHorPoulePlaces = array_values($fromHorPoule->getPlaces()->slice(0));
        while ($childRoundPlace = array_shift($childRoundPlaces)) {
            $bestFromPoule = $fromPoulePicker->getBestFromPoule(
                $childRoundPlace->getPoule(),
                array_map(fn (Place $place) => $place->getPoule(), $fromHorPoulePlaces),
                array_map(fn (Place $place) => $place->getPoule(), $childRoundPlaces)
            );
            $bestFromPlace = $this->removeBestHorizontalPlace($fromHorPoulePlaces, $bestFromPoule);
            $placeMapping = new QualifyByPlaceMapping($bestFromPlace, $childRoundPlace);
            $mappings->add($placeMapping);
            $this->possibleFromMap->addMappingToMap($placeMapping);
        }
        return $mappings;
    }

    /**
     * @param list<Place> $fromHorPoulePlaces
     * @param Poule $bestFromPoule
     * @return Place
     * @throws Exception
     */
    protected function removeBestHorizontalPlace(array &$fromHorPoulePlaces, Poule $bestFromPoule): Place
    {
        $bestPouleNr = $bestFromPoule->getNumber();
        $fromPlaces = array_filter($fromHorPoulePlaces, fn ($place) => $place->getPouleNr() === $bestPouleNr);
        $bestFromPlace = reset($fromPlaces);
        if ($bestFromPlace === false) {
            throw new Exception('fromPlace should be found', E_ERROR);
        }
        $idx = array_search($bestFromPlace, $fromHorPoulePlaces, true);
        if ($idx === false) {
            throw new Exception('fromPlace should be found', E_ERROR);
        }
        array_splice($fromHorPoulePlaces, $idx, 1);
        return $bestFromPlace;
    }
}
