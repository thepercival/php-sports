<?php

declare(strict_types=1);

namespace Sports\Qualify\Rule;

use Doctrine\Common\Collections\Collection;
use Exception;
use Sports\Place;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\PlaceMapping as QualifyPlaceMapping;
use Sports\Qualify\Rule as QualifyRule;
use Sports\Qualify\Target as QualifyTarget;

class Single extends QualifyRule
{
    private Single|null $next = null;

    /**
     * @param HorizontalPoule $fromHorizontalPoule
     * @param QualifyGroup $group
     * @param Collection<int, QualifyPlaceMapping> $placeMappings
     * @param Single|null $previous
     */
    public function __construct(
        HorizontalPoule $fromHorizontalPoule,
        QualifyGroup $group,
        private Collection $placeMappings,
        private Single|null $previous
    ) {
        parent::__construct($fromHorizontalPoule);
        $this->fromHorizontalPoule->setQualifyRule($this);
        if ($this->previous !== null) {
            $this->previous->setNext($this);
        } else {
            $group->setFirstSingleRule($this);
        }
    }

    /**
     * @return Collection<int, QualifyPlaceMapping>
     */
    public function getMappings(): Collection
    {
        return $this->placeMappings;
    }

    public function getToPlace(Place $fromPlace): Place
    {
        $mappings = $this->getMappings()->filter(function (QualifyPlaceMapping $placeMapping) use ($fromPlace): bool {
            return $placeMapping->getFromPlace() === $fromPlace;
        });
        $mapping = $mappings->first();
        if ($mapping === false) {
            throw new \Exception('could not find toPlace', E_ERROR);
        }
        return $mapping->getToPlace();
    }

    public function getFromPlace(Place $toPlace): Place
    {
        $mappings = $this->getMappings()->filter(function (QualifyPlaceMapping $placeMapping) use ($toPlace): bool {
            return $placeMapping->getToPlace() === $toPlace;
        });
        $mapping = $mappings->first();
        if ($mapping === false) {
            throw new \Exception('could not find fromPlace', E_ERROR);
        }
        return $mapping->getFromPlace();
    }

    public function getNrOfToPlaces(): int
    {
        return $this->placeMappings->count();
    }

    public function getPrevious(): Single | null
    {
        return $this->previous;
    }

    public function setNext(Single | null $next): void
    {
        $this->next = $next;
    }

    public function getNext(): Single | null
    {
        return $this->next;
    }

    public function setPrevious(Single | null $previous): void
    {
        $this->previous = $previous;
    }

    public function getNeighbour(QualifyTarget $targetSide): Single | null
    {
        return $targetSide === QualifyTarget::Winners ? $this->previous : $this->next;
    }

    public function getFirst(): Single
    {
        $previous = $this->getPrevious();
        if ($previous !== null) {
            return $previous->getFirst();
        }
        return $this;
    }

    public function getLast(): Single
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
        return $neighBour->getNrOfToPlaces() + $neighBour->getNrOfToPlacesTargetSide($targetSide);
    }

    public function detach(): void
    {
        $next = $this->getNext();
        if ($next !== null) {
            $next->detach();
            $this->setNext(null);
        }
        $this->getFromHorizontalPoule()->setQualifyRule(null);
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
