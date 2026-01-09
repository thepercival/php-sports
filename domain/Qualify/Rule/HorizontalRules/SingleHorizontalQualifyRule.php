<?php

declare(strict_types=1);

namespace Sports\Qualify\Rule\Horizontal;

use Doctrine\Common\Collections\Collection;
use Exception;
use Sports\Place;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Mapping\ByPlace as ByPlaceMapping;
use Sports\Qualify\Rule\HorizontalQualifyRuleInterface as HorizontalQualifyRule;
use Sports\Qualify\Rule\Horizontal\SingleHorizontalQualifyRule as HorizontalSingleQualifyRule;
use Sports\Qualify\Rule\SingleQualifyRuleInterface as SingleQualifyRule;
use Sports\Qualify\QualifyTarget as QualifyTarget;

final class SingleHorizontalQualifyRule extends HorizontalQualifyRule implements SingleQualifyRule
{
    private HorizontalSingleQualifyRule | null $next = null;

    /**
     * @param HorizontalPoule $fromHorizontalPoule
     * @param QualifyGroup $group
     * @param Collection<int, ByPlaceMapping> $byPlaceMappings
     * @param HorizontalSingleQualifyRule|null $previous
     */
    public function __construct(
        HorizontalPoule $fromHorizontalPoule,
        QualifyGroup $group,
        private Collection $byPlaceMappings,
        private HorizontalSingleQualifyRule|null $previous
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
     * @return Collection<int, ByPlaceMapping>
     */
    public function getMappings(): Collection
    {
        return $this->byPlaceMappings;
    }

//    public function getToPlace(Place $fromPlace): Place
//    {
//        $mappings = $this->getMappings()->filter(function (ByPlaceMapping $placeMapping) use ($fromPlace): bool {
//            return $placeMapping->getFromPlace() === $fromPlace;
//        });
//        $mapping = $mappings->first();
//        if ($mapping === false) {
//            throw new \Exception('could not find toPlace', E_ERROR);
//        }
//        return $mapping->getToPlace();
//    }
//
//    public function getFromPlace(Place $toPlace): Place
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
//    public function getByPlaceMapping(Place $toPlace): QualifyPlaceMapping {
//
//    }

    public function getMappingByToPlace(Place $toPlace): ByPlaceMapping|null
    {
        $mappings = $this->getMappings()->filter(function (ByPlaceMapping $placeMapping) use ($toPlace): bool {
            return $placeMapping->getToPlace() === $toPlace;
        });
        $mapping = $mappings->first();
        return $mapping !== false ? $mapping : null;
    }

    public function getMappingByFromPlace(Place $fromPlace): ByPlaceMapping|null
    {
        $mappings = $this->getMappings()->filter(function (ByPlaceMapping $placeMapping) use ($fromPlace): bool {
            return $placeMapping->getToPlace() === $fromPlace;
        });
        $mapping = $mappings->first();
        return $mapping !== false ? $mapping : null;
    }

    // if ($mapping === false) {
//            throw new \Exception('could not find toPlace', E_ERROR);
        // }
//        return $mapping;

//        try {
//            $this->getFromPlace($toPlace);
//            return true;
//        } catch ( \Exception $e ) {
//            return false;
//        }
//    }


    #[\Override]
    public function getNrOfMappings(): int
    {
        return $this->byPlaceMappings->count();
    }

    public function getPrevious(): SingleHorizontalQualifyRule | null
    {
        return $this->previous;
    }

    public function setNext(SingleHorizontalQualifyRule | null $next): void
    {
        $this->next = $next;
    }

    public function getNext(): SingleHorizontalQualifyRule | null
    {
        return $this->next;
    }

    public function setPrevious(SingleHorizontalQualifyRule | null $previous): void
    {
        $this->previous = $previous;
    }

    public function getNeighbour(QualifyTarget $targetSide): SingleHorizontalQualifyRule | null
    {
        return $targetSide === QualifyTarget::Winners ? $this->previous : $this->next;
    }

    public function getFirst(): HorizontalSingleQualifyRule
    {
        $previous = $this->getPrevious();
        if ($previous !== null) {
            return $previous->getFirst();
        }
        return $this;
    }

    public function getLast(): HorizontalSingleQualifyRule
    {
        $next = $this->getNext();
        if ($next !== null) {
            return $next->getLast();
        }
        return $this;
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

    public function detach(): void
    {
        $next = $this->getNext();
        if ($next !== null) {
            $next->detach();
            $this->setNext(null);
        }
        $this->getFromHorizontalPoule()->setQualifyRuleNew(null);
        $this->setPrevious(null);
    }

    public function getGroup(): QualifyGroup
    {
        $target = $this->getQualifyTarget();
        $firstSingleRule = $this->getFirst();
        $targetGroups = $this->getFromRound()->getTargetQualifyGroups($target);
        $qualifGroups = $targetGroups->filter(function (QualifyGroup $qualifyGroup) use ($firstSingleRule): bool {
            return $firstSingleRule === $qualifyGroup->getFirstSingleRule();
        });
        $qualifGroup = $qualifGroups->last();
        if ($qualifGroup === false) {
            throw new Exception('voor de single-kwalificatieregel kan geen groep worden gevonden', E_ERROR);
        }
        return $qualifGroup;
    }
}
