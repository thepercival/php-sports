<?php

namespace Sports\Structure;

use Sports\Place\Location as PlaceLocation;
use Sports\Structure\PathNode as StructurePathNode;

readonly class Location implements \Stringable
{
    public function __construct(
        private int               $categoryNr,
        private StructurePathNode $pathNode,
        private PlaceLocation     $placeLocation ) {
    }

    public function getCategoryNr(): int {
        return $this->categoryNr;
    }

    public function getPathNode(): StructurePathNode {
        return $this->pathNode;
    }

    public function getPlaceLocation(): PlaceLocation {
        return $this->placeLocation;
    }

    public function __toString(): string
    {
        return $this->categoryNr . '.' .
            $this->pathNode . '.' .
            $this->placeLocation->getUniqueIndex();
    }
}