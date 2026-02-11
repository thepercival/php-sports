<?php

declare(strict_types=1);

namespace Sports\Structure;

use Sports\Qualify\QualifyTarget;

final class PathNode implements \Stringable
{
    private PathNode|null $next = null;

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
        $this->next = new self($qualifyTarget, $qualifyGroupNumber, $this);
        return $this->next;
    }

    public function getNext(): PathNode|null
    {
        return $this->next;
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

    public function getRoot(): self
    {
        if( $this->previous !== null ) {
            return $this->previous->getRoot();
        }
        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        if ($this->previous === null) {
            return (string)$this->qualifyGroupNumber;
        }
        $qualifyTarget = ($this->qualifyTarget !== null ? $this->qualifyTarget->value : '');
        return (string)$this->previous . $qualifyTarget . $this->qualifyGroupNumber;
    }
}
