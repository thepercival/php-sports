<?php

declare(strict_types=1);

namespace Sports;

use Closure;
use Sports\Competitor\StartLocation;
use Sports\Exceptions\CellNotFoundException;
use Sports\Exceptions\NoStructureException;
use Sports\Exceptions\StructureNotFoundException;
use Sports\Round\Number as RoundNumber;

class Structure
{
    /**
     * @var non-empty-list<Category>
     */
    protected array $categories;

    /**
     * @param list<Category> $categories
     * @param RoundNumber $firstRoundNumber
     */
    public function __construct(array $categories, protected RoundNumber $firstRoundNumber)
    {
        if (count($categories) < 1) {
            throw new StructureNotFoundException('a structure should have at least 1 category', E_ERROR);
        }
        $this->categories = $categories;
    }

    /**
     * @return non-empty-list<Category>
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getCategory(int $number): Category|null
    {
        foreach ($this->categories as $category) {
            if ($category->getNumber() === $number) {
                return $category;
            }
        }
        return null;
    }

    public function getNextCategory(Category $category): Category|null
    {
        return $this->getCategory($category->getNumber() + 1);
    }

    public function hasSingleCategory(): bool
    {
        return count($this->getCategories()) === 1;
    }

    public function getSingleCategory(): Category
    {
        $categories = $this->getCategories();
        if (count($categories) !== 1) {
            throw new \Exception('There must be 1 category', E_ERROR);
        }
        $category = array_pop($categories);
        if ($category->getNumber() === 1) {
            return $category;
        }
        throw new \Exception('There must be at least 1 category', E_ERROR);
    }

    /**
     * @return non-empty-list<Round>
     */
    public function getRootRounds(): array
    {
        return array_map(function (Category $category): Round {
            return $category->getRootRound();
        }, $this->categories);
    }

    public function getFirstRoundNumber(): RoundNumber
    {
        return $this->firstRoundNumber;
    }

    public function getLastRoundNumber(): RoundNumber
    {
        $getLastRoundNumber = function (RoundNumber $roundNumber) use (&$getLastRoundNumber): RoundNumber {
            /** @var Closure(RoundNumber):RoundNumber $getLastRoundNumber */
            $next = $roundNumber->getNext();
            if ($next === null) {
                return $roundNumber;
            }
            return $getLastRoundNumber($next);
        };
        return $getLastRoundNumber($this->getFirstRoundNumber());
    }

    /**
     * @return list<RoundNumber>
     */
    public function getRoundNumbers(int|null $startRoundNumber = null): array
    {
        $roundNumbers = [];
        $roundNumber = $this->getFirstRoundNumber();
        while ($roundNumber !== null) {
            if( $startRoundNumber === null || $roundNumber->getNumber() >= $startRoundNumber) {
                array_push($roundNumbers, $roundNumber);
            }
            $roundNumber = $roundNumber->getNext();
        }
        return $roundNumbers;
    }

    public function getRoundNumber(int $roundNumberAsValue): ?RoundNumber
    {
        $roundNumber = $this->getFirstRoundNumber();
        while ($roundNumber !== null) {
            if ($roundNumber->getNumber() === $roundNumberAsValue) {
                return $roundNumber;
            }
            $roundNumber = $roundNumber->getNext();
        }
        return $roundNumber;
    }

    public function locationExists( StartLocation $startLocation ): bool {

        $category = $this->getCategory($startLocation->getCategoryNr());
        if( $category === null ) {
            return false;
        }
        foreach( $this->getRoundNumbers() as $roundNumber ) {
            try {
                $cell = $roundNumber->getStructureCell($category);
                foreach( $cell->getPoules() as $poule ) {
                    if( $poule->getNumber() !== $startLocation->getPouleNr() ) {
                        continue;
                    }
                    return $startLocation->getPlaceNr() <= count($poule->getPlaces());
                }
            }  catch(CellNotFoundException $e ){
            }
        }
        return false;
    }
}
