<?php
declare(strict_types=1);

namespace Sports;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use SportsHelpers\Identifiable;
use Sports\Formation\Line;

class Formation extends Identifiable
{
    /**
     * @var ArrayCollection<int|string, Line>|PersistentCollection<int|string, Line>
     * @psalm-var ArrayCollection<int|string, Line>
     */
    protected ArrayCollection|PersistentCollection $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
    }

    /**
     * @return ArrayCollection<int|string, Line>|PersistentCollection<int|string, Line>
     * @psalm-return ArrayCollection<int|string, Line>
     */
    public function getLines(): ArrayCollection|PersistentCollection
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
}
