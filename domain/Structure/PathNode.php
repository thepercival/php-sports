<?php

declare(strict_types=1);

namespace Sports\Structure;

use Sports\Qualify\Target as QualifyTarget;

class PathNode implements \Stringable
{
    public function __construct(
        private QualifyTarget|null $qualifyTarget,
        private int $qualifyGroupNumber,
        private PathNode|null $previous
    ) {
        if( $qualifyGroupNumber < 1 ) {
            throw new \Exception('qualifyGroupNumber must be a positive number');
        }
    }

    public function createNext(QualifyTarget $qualifyTarget, int $qualifyGroupNumber): PathNode
    {
        $path = new PathNode($qualifyTarget, $qualifyGroupNumber, $this);
        // this.next = path;
        return $path;
    }

    public function getQualifyTarget(): QualifyTarget|null
    {
        return $this->qualifyTarget;
    }

    public function getQualifyGroupNumber(): int
    {
        return $this->qualifyGroupNumber;
    }

    public function getPrevious(): self|null
    {
        return $this->previous;
    }

    public function hasPrevious(): bool
    {
        return $this->previous !== null;
    }

    public function __toString(): string
    {
        if ($this->previous === null) {
            return (string)$this->qualifyGroupNumber;
        }
        $qualifyTarget = ($this->qualifyTarget !== null ? $this->qualifyTarget->value : '');
        return (string)$this->previous . $qualifyTarget . $this->qualifyGroupNumber;
    }
}
