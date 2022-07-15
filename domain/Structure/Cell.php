<?php

namespace Sports\Structure;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Category;
use Sports\Structure\Cell as StructureCell;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\Identifiable;

class Cell extends Identifiable
{
    /**
     * @var Collection<int|string, Round>
     */
    protected Collection $rounds;

    public function __construct(
        private Category $category,
        private RoundNumber $roundNumber
    ) {
        $this->rounds = new ArrayCollection();
        if (!$category->getStructureCells()->contains($this)) {
            $category->getStructureCells()->add($this);
        }
        if (!$roundNumber->getStructureCells()->contains($this)) {
            $roundNumber->getStructureCells()->add($this);
        }
    }

    public function isFirst(): bool
    {
        return $this->getRoundNumber()->getNumber() === 1;
    }

    public function getNext(): Cell|null
    {
        $nextRoundNumber = $this->getRoundNumber()->getNext();
        if ($nextRoundNumber === null) {
            return null;
        }
        try {
            return $nextRoundNumber->getStructureCell($this->getCategory());
        } catch (\Exception $e) {
        }
        return null;
    }

    public function getRoundNumber(): RoundNumber
    {
        return $this->roundNumber;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getCategoryNr(): int
    {
        return $this->getCategory()->getNumber();
    }

    public function createNext(): StructureCell
    {
        $nextRoundNumber = $this->roundNumber->getNext();
        if ($nextRoundNumber === null) {
            $nextRoundNumber = $this->roundNumber->createNext();
        }
        return new StructureCell($this->category, $nextRoundNumber);
    }

    public function needsRanking(): bool
    {
        foreach ($this->getRounds() as $round) {
            if ($round->needsRanking()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Collection<int|string, Round>
     */
    public function getRounds(): Collection
    {
        return $this->rounds;
    }

//    public function getPoules(): Poule[] {
//        let poules: Poule[] = [];
//            this.getRounds().forEach((round: Round) => {
//            poules = poules.concat(round.getPoules());
//        });
//        return poules;
//    }
//
//    hasNext(): boolean {
//    return this.next !== undefined;
    //}

    public function detach(): void
    {
        $nextRoundNumber = $this->getRoundNumber()->getNext();
        if ($nextRoundNumber !== null) {
            try {
                $next = $this->getCategory()->getStructureCell($nextRoundNumber);
                $next->detach();
            } catch (\Exception $e) {
            }
        }

        $structureCells = $this->getRoundNumber()->getStructureCells();
        $structureCells->removeElement($this);

        if (count($structureCells) === 0) {
            $this->getRoundNumber()->detach();
        }
    }
}
