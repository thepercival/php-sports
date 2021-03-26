<?php
declare(strict_types=1);

namespace Sports\Qualify;

use Sports\Round\Number as RoundNumber;
use Sports\Round;
use Sports\Poule\Horizontal as HorizontalPoule;
use SportsHelpers\Identifiable;

class Group extends Identifiable
{
    protected int $number;
    protected Round $childRound;
    /**
     * @var list<HorizontalPoule>
     */
    protected array $horizontalPoules = [];

    const WINNERS = 1;
    const DROPOUTS = 2;
    const LOSERS = 3;

    public function __construct(
        protected Round $round,
        protected int $winnersOrLosers,
        RoundNumber $nextRoundNumber,
        int $number = null
    ) {
        $this->setWinnersOrLosers($winnersOrLosers);
        $this->number = $number !== null ? $number : $round->getWinnersOrLosersQualifyGroups($winnersOrLosers)->count() + 1;
        if ($number === null) {
            $this->addQualifyGroup($round);
        } else {
            $this->insertQualifyGroupAt($round, $number);
        }
        $this->childRound = new Round($nextRoundNumber, $this);
    }

    public function getWinnersOrLosers(): int
    {
        return $this->winnersOrLosers;
    }

    public function setWinnersOrLosers(int $winnersOrLosers): void
    {
        $this->winnersOrLosers = $winnersOrLosers;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    protected function insertQualifyGroupAt(Round $round, int $insertAt): void
    {
        $qualifyGroups = $round->getWinnersOrLosersQualifyGroups($this->getWinnersOrLosers());
        if (!$qualifyGroups->contains($this)) {
            $round->addQualifyGroup($this);
            // sort auto because of sort-config in db-yml
        }
    }

    public function addQualifyGroup(Round $round): void
    {
        $qualifyGroups = $round->getWinnersOrLosersQualifyGroups($this->getWinnersOrLosers());
        if (!$qualifyGroups->contains($this)) {
            $round->addQualifyGroup($this);
        }
    }

    public function getChildRound(): Round
    {
        return $this->childRound;
    }

    public function setChildRound(Round $childRound): void
    {
        $this->childRound = $childRound;
    }

    /**
     * @return list<HorizontalPoule>
     */
    public function &getHorizontalPoules(): array
    {
        return $this->horizontalPoules;
    }

    public function isBorderGroup(): bool
    {
        $qualifyGroups = $this->getRound()->getWinnersOrLosersQualifyGroups($this->getWinnersOrLosers());
        return $this === $qualifyGroups->last();
    }

    // public function isInBorderHoritontalPoule(Place $place ): bool {
    //     $borderHorizontalPoule = $this->getHorizontalPoules()->last();
    //     return $borderHorizontalPoule->hasPlace($place);
    // }

    public function getBorderPoule(): HorizontalPoule
    {
        return $this->horizontalPoules[count($this->horizontalPoules)-1];
    }

    public function getNrOfPlaces(): int
    {
        return count($this->getHorizontalPoules()) * $this->getRound()->getPoules()->count();
    }

    public function getNrOfToPlacesTooMuch(): int
    {
        return $this->getNrOfPlaces() - $this->getChildRound()->getNrOfPlaces();
    }

    public function getNrOfQualifiers(): int
    {
        $nrOfQualifiers = 0;
        foreach ($this->getHorizontalPoules() as $horizontalPoule) {
            $nrOfQualifiers += $horizontalPoule->getNrOfQualifiers();
        }
        return $nrOfQualifiers;
    }
}
