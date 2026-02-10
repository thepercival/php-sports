<?php

declare(strict_types=1);

namespace Sports;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Exceptions\StructureNotFoundException;
use Sports\Game\State as GameState;
use Sports\Round\Number as RoundNumber;
use Sports\Structure\Cell as StructureCell;
use SportsHelpers\Identifiable;
use InvalidArgumentException;

/**
 * @api
 */
final class Category extends Identifiable
{
    public const int MAX_LENGTH_NAME = 15;
    public const int MAX_LENGTH_ABBREVIATION = 2;
    public const string DEFAULTNAME = 'standaard';
    protected string $name;
    protected int $number;
    protected string|null $abbreviation = null;

    /**
     * @var Collection<int|string, StructureCell>
     */
    protected Collection $structureCells;

//    protected StructureCell|null $firstStructureCell = null;

    public function __construct(protected Competition $competition, string $name, int|null $number = null)
    {
        $this->number = $number ?? count($competition->getCategories()) + 1;
        $this->setName($name);
        $this->structureCells = new ArrayCollection();
        if (!$competition->getCategories()->contains($this)) {
            $competition->getCategories()->add($this);
        }
    }

    public function isSingle(): bool {
        return count($this->getCompetition()->getCategories()) === 1;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

//    public function setNumber(int $number): void
//    {
//        $this->number = $number;
//    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        if (strlen($name) > self::MAX_LENGTH_NAME) {
            throw new InvalidArgumentException(
                'de naam mag maximaal ' . self::MAX_LENGTH_NAME . ' karakters bevatten',
                E_ERROR
            );
        }
        $this->name = $name;
    }

    public function getAbbreviation(): string|null
    {
        return $this->abbreviation;
    }

    public function setAbbreviation(string|null $abbreviation): void
    {
        if ($abbreviation !== null && strlen($abbreviation) > self::MAX_LENGTH_ABBREVIATION) {
            throw new InvalidArgumentException(
                'de afkortinh mag maximaal ' . self::MAX_LENGTH_ABBREVIATION . ' karakters bevatten',
                E_ERROR
            );
        }
        $this->abbreviation = $abbreviation;
    }

    /**
     * @return Collection<int|string, StructureCell>
     */
    public function getStructureCells(): Collection
    {
        return $this->structureCells;
    }

    public function getFirstStructureCell(): StructureCell
    {
        return $this->getStructureCellByValue(1);
    }

    public function getLastStructureCell(): StructureCell
    {
        $structureCell = $this->getFirstStructureCell();
        $nextStructureCell = $structureCell->getNext();
        while ($nextStructureCell !== null) {
            $structureCell = $nextStructureCell;
            $nextStructureCell = $nextStructureCell->getNext();
        }
        return $structureCell;
    }

    public function getStructureCell(RoundNumber $roundNumber): StructureCell
    {
        return $this->getStructureCellByValue($roundNumber->getNumber());
    }

    public function getStructureCellByValue(int $roundNumber): StructureCell
    {
        foreach ($this->structureCells as $structureCell) {
            if ($structureCell->getRoundNumber()->getNumber() === $roundNumber) {
                return $structureCell;
            }
        }
        throw new \Exception('de structuurcel kan niet gevonden worden');
    }

    public function getGamesState(): GameState
    {
        $firstStructureCell = $this->getFirstStructureCell();
        $firstGameState = $firstStructureCell->getGamesState();
        if ($firstGameState !== GameState::Finished) {
            return $firstGameState;
        }

        $lastStructureCell = $this->getLastStructureCell();
        $lastGameState = $lastStructureCell->getGamesState();
        if ($lastGameState === GameState::Finished) {
            return GameState::Finished;
        }
        return GameState::InProgress;
    }

    public function getRootRound(): Round
    {
        $structureCell = $this->getStructureCellByValue(1);
        $rounds = $structureCell->getRounds();
        if ($rounds->count() !== 1) {
            throw new StructureNotFoundException('there must be 1 rootRound, "' . count($rounds) . '" given', E_ERROR);
        }
        $rootRound = $rounds->first();
        if ($rootRound === false) {
            throw new StructureNotFoundException('there must be 1 rootRound, "' . count($rounds) . '" given', E_ERROR);
        }
        return $rootRound;
    }

    public function hasMultipleSports(): bool
    {
        return $this->getCompetition()->hasMultipleSports();
    }
//    public function hasBegun(): bool
//    {
//        foreach ($this->getRounds() as $round) {
//            if ($round->hasBegun()) {
//                return true;
//            }
//        }
//        return false;
//    }

    public function getCompetition(): Competition
    {
        return $this->competition;
    }

    /**
     * @return Collection<int|string, CompetitionSport>
     */
    public function getCompetitionSports(): Collection
    {
        return $this->getCompetition()->getSports();
    }
}
