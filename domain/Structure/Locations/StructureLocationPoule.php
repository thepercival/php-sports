<?php

namespace Sports\Structure\Locations;

use Sports\Place\Location as PlaceLocation;
use Sports\Structure\PathNode as StructurePathNode;

final readonly class StructureLocationPoule extends Location implements \Stringable
{
    public function __construct( int $categoryNr, StructurePathNode $pathNode, private int $pouleNr ) {
        parent::__construct($categoryNr, $pathNode);
    }

    public function getPouleNr(): int {
        return $this->pouleNr;
    }

    #[\Override]
    public function __toString(): string
    {
        return parent::__toString() . '.' . $this->pouleNr;
    }
}