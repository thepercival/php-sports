<?php
declare(strict_types=1);

namespace Sports\Structure;

class PathNode implements \Stringable
{
    public function __construct(
        private string|null $qualifyTarget,
        private int $qualifyGroupNumber,
        private PathNode|null $previous
    )
    {
    }

    public function createNext(string $qualifyTarget, int $qualifyGroupNumber): PathNode
    {
        $path = new PathNode($qualifyTarget, $qualifyGroupNumber, $this);
        // this.next = path;
        return $path;
    }

    public function __toString()
    {
        $val = $this->qualifyTarget === null ? '' : $this->qualifyTarget . $this->qualifyGroupNumber;
        return $this->previous === null ? $val : $this->previous . $val;
    }
}
