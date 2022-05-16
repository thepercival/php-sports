<?php

declare(strict_types=1);

namespace Sports;

use Ahamed\JsPhp\JsArray;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Exceptions\NoStructureException;
use SportsHelpers\Identifiable;
use InvalidArgumentException;

class Category extends Identifiable
{
    public const MAX_LENGTH_NAME = 20;
    public const DEFAULTNAME = 'standaard';
    protected int $number;
    protected string $name;
    /**
     * @var Collection<int|string, Round>
     */
    protected Collection $rounds;
    protected Round|null $rootRound = null;

    public function __construct(protected Competition $competition, string $name, int|null $number = null)
    {
        $this->number = $number ?? count($competition->getCategories()) + 1;
        $this->setName($name);
        $this->rounds = new ArrayCollection();
        if (!$competition->getCategories()->contains($this)) {
            $competition->getCategories()->add($this);
        }
    }

    public function getNumber(): int
    {
        return $this->number;
    }

//    public function setCompetition(Competition $competition): void
//    {
//        $this->competition = $competition;
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

    /**
     * @return Collection<int|string, Round>
     */
    public function getRounds(): Collection
    {
        return $this->rounds;
    }

    public function getRootRound(): Round
    {
        $rootRound = $this->rootRound;
        if ($rootRound !== null) {
            return $rootRound;
        }
        foreach ($this->rounds as $round) {
            if ($round->getParent() === null) {
                $this->rootRound = $round;
                return $round;
            }
        }
        throw new NoStructureException('there must be 1 rootRound, "' . count($this->rounds) . '" given', E_ERROR);
    }

    public function setRootRound(Round $round): void
    {
        $this->rootRound = $round;
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
