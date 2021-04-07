<?php
declare(strict_types=1);

namespace Sports\Structure;

class PathNode
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

    public function nodeToString(): string
    {
        return $this->qualifyTargettoString() . $this->qualifyGroupNumber;
    }

    public function pathToString(): string
    {
        if ($this->previous === null) {
            return $this->nodeToString();
        }
        return $this->previous->pathToString() . $this->nodeToString();
    }

    protected function qualifyTargettoString(): string
    {
        return $this->qualifyTarget ?? '';
    }
}
