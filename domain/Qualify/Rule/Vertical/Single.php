<?php

declare(strict_types=1);

namespace Sports\Qualify\Rule\Vertical;

use Doctrine\Common\Collections\Collection;
use Exception;
use Sports\Place;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Mapping as QualifyPlaceMapping;
use Sports\Qualify\Mapping\ByPlace as ByPlaceMapping;
use Sports\Qualify\Rule\Vertical as VerticalQualifyRule;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Qualify\Mapping\ByRank as ByRankMapping;
use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Qualify\Rule\Vertical\Single as VerticalSingleQualifyRule;
use Sports\Qualify\Rule\Vertical\Multiple as VerticalMultipleQualifyRule;

class Single extends VerticalQualifyRule implements SingleQualifyRule
{
    protected VerticalSingleQualifyRule|null $next = null;

    /**
     * @param HorizontalPoule $fromHorizontalPoule
     * @param QualifyGroup $group
     * @param Collection<int, ByRankMapping> $byRankMappings
     * @param VerticalSingleQualifyRule|null $previous
     */
    public function __construct(
        HorizontalPoule $fromHorizontalPoule,
        QualifyGroup $group,
        private Collection $byRankMappings,
        private VerticalSingleQualifyRule|null $previous
    ) {
        parent::__construct($fromHorizontalPoule);
        $this->fromHorizontalPoule->setQualifyRuleNew($this);
        if ($this->previous !== null) {
            $this->previous->setNext($this);
        } else {
            $group->setFirstSingleRule($this);
        }
    }

    /**
     * @return Collection<int, ByRankMapping>
     */
    public function getMappings(): Collection
    {
        return $this->byRankMappings;
    }

//    public function getToPlace(Place $fromPlace): Place
//    {
//        $mappings = $this->getMappings()->filter(function (QualifyPlaceMapping $placeMapping) use ($fromPlace): bool {
//            return $placeMapping->getFromPlace() === $fromPlace;
//        });
//        $mapping = $mappings->first();
//        if ($mapping === false) {
//            throw new \Exception('could not find toPlace', E_ERROR);
//        }
//        return $mapping->getToPlace();
//    }

    /**
     * @return list<Place>
     */
    public function getToPlaces(): array
    {
        return array_values( $this->getMappings()->map(function (QualifyPlaceMapping $placeMapping): Place {
            return $placeMapping->getToPlace();
        })->toArray() );
    }

//    public function getFromRank(Place $toPlace): Place
//    {
//        $mappings = $this->getMappings()->filter(function (QualifyPlaceMapping $placeMapping) use ($toPlace): bool {
//            return $placeMapping->getToPlace() === $toPlace;
//        });
//        $mapping = $mappings->first();
//        if ($mapping === false) {
//            throw new \Exception('could not find fromPlace', E_ERROR);
//        }
//        return $mapping->getFromPlace();
//    }
//
//    public function getMappingByRank(Place $toPlace): int
//    {
//        $rank = 1;
//        foreach( $this->getMappings() as $mapping ) {
//             if( $mapping->getToPlace() === $toPlace ) {
//                 return $rank;
//             } else {
//                 $rank++;
//             }
//        }
//        return 0;
//    }

//    public function hasToPlace(Place $toPlace): bool {
//        try {
//            $this->getFromPlace($toPlace);
//            return true;
//        } catch ( \Exception $e ) {
//            return false;
//        }
//    }

    public function getNrOfMappings(): int
    {
        return $this->byRankMappings->count();
    }

//    public function getNrOfDropouts(): int {
//        return $this->fromHorizontalPoule->getPlaces()->count() - $this->getNrOfToPlaces();
//    }

    public function getMappingByToPlace(Place $toPlace): ByRankMapping|null
    {
        $mappings = $this->getMappings()->filter(function (ByRankMapping $placeMapping) use ($toPlace): bool {
            return $placeMapping->getToPlace() === $toPlace;
        });
        $mapping = $mappings->first();
        return $mapping !== false ? $mapping : null;
    }

//    public function getMappingByFromPlace(Place $fromPlace): ByRankMapping|null
//    {
//        $mappings = $this->getByRankMappings()->filter(function (ByRankMapping $placeMapping) use ($fromPlace): bool {
//            return $placeMapping->getToPlace() === $fromPlace;
//        });
//        $mapping = $mappings->first();
//        return $mapping !== false ? $mapping : null;
//    }

//    public function setNext(Single | null $next): void
//    {
//        $this->next = $next;
//    }
//
//    public function getNext(): Single | null
//    {
//        return $this->next;
//    }
//
//    public function setPrevious(Single | null $previous): void
//    {
//        $this->previous = $previous;
//    }
//
//    public function getNeighbour(QualifyTarget $targetSide): Single | null
//    {
//        return $targetSide === QualifyTarget::Winners ? $this->previous : $this->next;
//    }

    public function getFirst(): VerticalSingleQualifyRule | VerticalMultipleQualifyRule
    {
        $previous = $this->getPrevious();
        if ($previous !== null) {
            return $previous->getFirst();
        }
        return $this;
    }

    public function getLast(): VerticalSingleQualifyRule | VerticalMultipleQualifyRule
    {
        $next = $this->getNext();
        if ($next !== null) {
            return $next->getLast();
        }
        return $this;
    }

    public function getNeighbour(QualifyTarget $targetSide): self|null {
        return $targetSide === QualifyTarget::Winners ? $this->previous : $this->next;
    }

    public function getNrOfToPlacesTargetSide(QualifyTarget $targetSide): int
    {
        $nrOfToPlacesTargetSide = 0;
        $neighBour = $this->getNeighbour($targetSide);
        if ($neighBour === null) {
            return $nrOfToPlacesTargetSide;
        }
        return $neighBour->getNrOfMappings() + $neighBour->getNrOfToPlacesTargetSide($targetSide);
    }

    public function getPrevious(): VerticalSingleQualifyRule|null {
        return $this->previous;
    }

    public function setNext(VerticalSingleQualifyRule|null $next): void {
        $this->next = $next;
    }

    public function getNext(): VerticalSingleQualifyRule|null {
        return $this->next;
    }

    public function setPrevious(VerticalSingleQualifyRule|null $previous): void {
        $this->previous = $previous;
    }

    public function detach(): void {
        $next = $this->getNext();
        if ($next !== null) {
            $next->detach();
            $this->setNext(null);
        }
        $this->getFromHorizontalPoule()->setQualifyRuleNew(null);
        $this->setPrevious(null);
    }

//
//    public function detach(): void
//    {
//        $next = $this->getNext();
//        if ($next !== null) {
//            $next->detach();
//            $this->setNext(null);
//        }
//        $this->getFromHorizontalPoule()->setQualifyRule(null);
//        $this->setPrevious(null);
//    }
//
//    public function getGroup(): QualifyGroup
//    {
//        $target = $this->getQualifyTarget();
//        $firstSingleRule = $this->getFirst();
//        $targetGroups = $this->getFromRound()->getTargetQualifyGroups($target);
//        $qualifGroups = $targetGroups->filter(function (QualifyGroup $qualifyGroup) use ($firstSingleRule): bool {
//            return $firstSingleRule === $qualifyGroup->getFirstSingleRule();
//        });
//        $qualifGroup = $qualifGroups->last();
//        if ($qualifGroup === false) {
//            throw new Exception('voor de single-kwalificatieregel kan geen groep worden gevonden', E_ERROR);
//        }
//        return $qualifGroup;
//    }
}
