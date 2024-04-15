<?php

namespace Sports\Structure\Locations;

use Sports\Place\Location as PlaceLocation;
use Sports\Structure\PathNode as StructurePathNode;

readonly class StructureLocationPoule extends Location implements \Stringable
{
    public function __construct( int $categoryNr, StructurePathNode $pathNode, private int $pouleNr ) {
        parent::__construct($categoryNr, $pathNode);
    }

    public function getCategoryNr(): int {
        return $this->categoryNr;
    }

    public function getPathNode(): StructurePathNode {
        return $this->pathNode;
    }

    public function getPouleNr(): int {
        return $this->pouleNr;
    }

    public function __toString(): string
    {
        return parent::__toString() . '.' . $this->pouleNr;
    }
}