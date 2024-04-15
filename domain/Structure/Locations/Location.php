<?php

namespace Sports\Structure\Locations;

use Sports\Place\Location as PlaceLocation;
use Sports\Structure\PathNode as StructurePathNode;

abstract readonly class Location implements \Stringable
{
    public function __construct(protected int $categoryNr, protected StructurePathNode $pathNode ) {
    }

    public function getCategoryNr(): int {
        return $this->categoryNr;
    }

    public function getPathNode(): StructurePathNode {
        return $this->pathNode;
    }

    public function __toString(): string
    {
        return $this->categoryNr . '.' . $this->pathNode;
    }
}