<?php

declare(strict_types=1);

namespace Sports\Qualify\Rule\Creator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use Sports\Place;
use Sports\Poule;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\FromPoulePicker;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\PlaceMapping as QualifyPlaceMapping;
use Sports\Qualify\PossibleFromMap;
use Sports\Qualify\Rule\Vertical\Multiple as VerticalMultipleQualifyRule;
use Sports\Qualify\Rule\Vertical\Single as VerticalSingleQualifyRule;
use Sports\Round;

class Vertical
{
    public function __construct()
    {
    }

    /**
     * @param list<HorizontalPoule> $fromHorPoules
     * @param QualifyGroup $qualifyGroup
     */
    public function createRules(array $fromHorPoules, QualifyGroup $qualifyGroup): void
    {
        $childRound = $qualifyGroup->getChildRound();
        $childPlaces = $this->getRoundPlaces($childRound, $qualifyGroup->getTarget());

        $previous = null; // : VerticalSingleQualifyRule |  undefined;
        foreach( $fromHorPoules as $fromHorPoule ) { // fromRoundHorPoules.every((fromHorPoule: HorizontalPoule): boolean => {
            $fromHorPoulePlaces = array_values( array_slice($fromHorPoule->getPlaces()->toArray(), 0 ) );
            while ( count($fromHorPoulePlaces) > 0 && count($childPlaces) > 0) {

                // SingleRule
                if (count($fromHorPoulePlaces) <= count($childPlaces)) {
                    $placeMappings = new ArrayCollection( $this->fromPlacesToMappings($fromHorPoulePlaces, $childPlaces) );
                    $previous = new VerticalSingleQualifyRule($fromHorPoule, $qualifyGroup, $placeMappings, $previous);
                } else {
                    $toPlaces = [];

                    $fromHorPoulePlace = array_shift($fromHorPoulePlaces);
                    while ($fromHorPoulePlace !== null && count($childPlaces) > 0) {

                        $toPlaces[] = array_shift($childPlaces);

                        // placeMappings.push(new QualifyPlaceMapping(fromHorPoulePlace, childPlace));
                        $fromHorPoulePlace = array_shift($fromHorPoulePlaces);
                    }
                    new VerticalMultipleQualifyRule($fromHorPoule, $qualifyGroup, $toPlaces);
                }
            }
        }
        // console.log(qualifyGroup.getFirstVerticalRule());
    }

    // protected shiftChildPlace(childPlaces: Place[], childPlacesByPoule: (Place[])[]): Place|undefined {

    // }

    /**
     * @param Round $round
     * @param QualifyTarget $target
     * @return list<Place>
     */
    protected function getRoundPlaces(Round $round, QualifyTarget $target): array {
        $roundPlacesByPoule = $this->getRoundPlacesByPoule($round, $target);
        $roundPlaces = [];
        foreach( $roundPlacesByPoule as $pouleRoundPlaces ) {
            if ( $target === QualifyTarget::Losers) {
                $roundPlaces = array_merge($roundPlaces, $pouleRoundPlaces);
            } else {
                $roundPlaces = array_merge($roundPlaces, $pouleRoundPlaces);
            }
        }
        return $roundPlaces;
    }

    /**
     * @param Round $round
     * @param QualifyTarget $target
     * @return list<list<Place>>
     */
    protected function getRoundPlacesByPoule(Round $round, QualifyTarget $target): array {
        if ( $target === QualifyTarget::Losers) {
            $poules = array_reverse( $round->getPoules()->toArray() );
            return array_values( array_map( function (Poule $poule): array {
                return array_values( $poule->getPlaces()->toArray() );
            }, $poules ) );
        }
        return array_values( array_map( function(Poule $poule): array {
            return array_values( array_slice( $poule->getPlaces()->toArray(), 0 ) );
        }, $round->getPoules()->toArray() ) );
    }

    /**
     * @param list<Place> $fromHorPoulePlaces
     * @param list<Place> $childPlaces
     * @return list<QualifyPlaceMapping>
     */
    protected function fromPlacesToMappings(array &$fromHorPoulePlaces, array &$childPlaces): array {
        $mapping = [];
        $fromHorPoulePlace = array_shift($fromHorPoulePlaces);
        while ($fromHorPoulePlace !== null) {

            $childPoulePlace = array_shift($childPlaces);
            if( $childPoulePlace === null ) {
                throw new Exception('childPoulePlace should not be null', E_ERROR);
            }
            $mapping[] =  new QualifyPlaceMapping($fromHorPoulePlace, $childPoulePlace );
            $fromHorPoulePlace = array_shift($fromHorPoulePlaces);
        }
        return $mapping;
    }
}
