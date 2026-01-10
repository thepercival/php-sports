<?php

namespace Sports\Structure\Locations;

use Sports\Place\Location as PlaceLocation;
use Sports\Structure\PathNode as StructurePathNode;

final readonly class StructureLocationPlace extends Location implements \Stringable
{
    public function __construct(int $categoryNr, StructurePathNode $pathNode, private PlaceLocation $placeLocation ) {
        parent::__construct($categoryNr, $pathNode);
    }

    public function getPlaceLocation(): PlaceLocation {
        return $this->placeLocation;
    }

    #[\Override]
    public function __toString(): string
    {
        return parent::__toString() . '.' . $this->placeLocation->getUniqueIndex();
    }
}