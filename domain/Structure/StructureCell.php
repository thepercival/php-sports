<?php

namespace Sports\Structure;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Category;
use Sports\Game\GameState as GameState;
use Sports\Poule;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\Identifiable;
use SportsHelpers\PouleStructures\PouleStructure;

final class StructureCell extends Identifiable
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

    public function getNext(): StructureCell|null
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

    public function isLast(): bool
    {
        return $this->getNext() !== null;
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

    /**
     * @return list<Poule>
     */
    public function getPoules(): array
    {
        $poules = [];
        foreach ($this->getRounds() as $round) {
            $poules = array_merge($poules, $round->getPoules()->toArray());
        }
        return array_values($poules);
    }


    public function createPouleStructure(): PouleStructure
    {
        $placesPerPoule = [];
        foreach ($this->getPoules() as $poule) {
            $placesPerPoule[] = $poule->getPlaces()->count();
        }
        return new PouleStructure($placesPerPoule);
    }

    public function getGamesState(): GameState
    {
        $allPlayed = true;
        foreach ($this->getRounds() as $round) {
            if ($round->getGamesState() !== GameState::Finished) {
                $allPlayed = false;
                break;
            }
        }
        if ($allPlayed) {
            return GameState::Finished;
        }
        foreach ($this->getRounds() as $round) {
            if ($round->getGamesState() !== GameState::Created) {
                return GameState::InProgress;
            }
        }
        return GameState::Created;
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
