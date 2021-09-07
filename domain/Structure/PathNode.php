<?php
declare(strict_types=1);

namespace Sports\Structure;

class PathNode implements \Stringable
{
    public function __construct(
        private string|null $qualifyTarget,
        private int $qualifyGroupNumber,
        private PathNode|null $previous
    ) {
    }

    public function createNext(string $qualifyTarget, int $qualifyGroupNumber): PathNode
    {
        $path = new PathNode($qualifyTarget, $qualifyGroupNumber, $this);
        // this.next = path;
        return $path;
    }

    public function __toString()
    {
        if( $this->previous === null ) {
            return (string)$this->qualifyGroupNumber;
        }
        return (string)$this->previous . (string)$this->qualifyTarget . $this->qualifyGroupNumber;
    }
}
