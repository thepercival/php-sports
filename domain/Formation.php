<?php

declare(strict_types=1);

namespace Sports;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Formation\Line;
use SportsHelpers\Identifiable;

class Formation extends Identifiable
{
    /**
     * @var Collection<int|string, Line>
     */
    protected Collection $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
    }

    /**
     * @return Collection<int|string, Line>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function getLine(int $lineNumber): Line
    {
        $filtered = $this->lines->filter(function (Line $line) use ($lineNumber): bool {
            return $line->getNumber() === $lineNumber;
        });
        $firstLine = $filtered->first();
        if ($firstLine === false) {
            throw new \Exception('the line "' . $lineNumber . '" could not be found', E_ERROR);
        }
        return $firstLine;
    }

    public function getName(): string
    {
        return implode("-", array_map(function (Line $line): int {
            return $line->getNrOfPersons();
        }, $this->getLines()->toArray()));
    }

    public function equals(Formation $formation): bool {
        $lines = $this->lines->toArray();
        foreach( $formation->getLines() as $formationLine) {
            $thisLines = array_filter( $lines, function(Line $line) use ($formationLine) : bool {
                return $line->getNumber() === $formationLine->getNumber();
            } );
            $thisLine = array_shift($thisLines);
            if( $thisLine === null || !$formationLine->equals($thisLine) ) {
                return false;
            }
        }
        return $formation->getLines()->count() === $this->getLines()->count();
}
}
