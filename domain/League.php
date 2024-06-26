<?php

declare(strict_types=1);

namespace Sports;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use InvalidArgumentException;
use SportsHelpers\Identifiable;

class League extends Identifiable
{
    protected string $name;
    protected string|null $abbreviation;
    /**
     * @var Collection<int|string, Competition>
     */
    protected Collection $competitions;
    protected Association $association;

    public const int MIN_LENGTH_NAME = 3;
    public const int MAX_LENGTH_NAME = 60;
    public const int MAX_LENGTH_ABBREVIATION = 7;
    public const int MAX_LENGTH_SPORT = 30;

    public function __construct(Association $association, string $name, string |null $abbreviation = null)
    {
        $this->setAssociation($association);
        $this->setName($name);
        $this->setAbbreviation($abbreviation);
        $this->competitions = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        if (strlen($name) === 0) {
            throw new InvalidArgumentException("de naam moet gezet zijn", E_ERROR);
        }

        if (strlen($name) < self::MIN_LENGTH_NAME or strlen($name) > self::MAX_LENGTH_NAME) {
            throw new InvalidArgumentException("de naam moet minimaal ".self::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".self::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
        }
        $this->name = $name;
    }

    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    public function setAbbreviation(string $abbreviation = null): void
    {
        if ($abbreviation !== null && strlen($abbreviation) === 0) {
            $abbreviation = null;
        }
        if ($abbreviation !== null && strlen($abbreviation) > self::MAX_LENGTH_ABBREVIATION) {
            throw new InvalidArgumentException("de afkorting mag maximaal ".self::MAX_LENGTH_ABBREVIATION." karakters bevatten", E_ERROR);
        }
        $this->abbreviation = $abbreviation;
    }

    public function getAssociation(): Association
    {
        return $this->association;
    }

    protected function setAssociation(Association $association): void
    {
        $leagues = $association->getLeagues();
        if (!$leagues->contains($this)) {
            $leagues->add($this) ;
        }
        $this->association = $association;
    }

    /**
     * @return Collection<int|string, Competition>
     */
    public function getCompetitions(): Collection
    {
        return $this->competitions;
    }

    public function getCompetition(Season $season): Competition|null
    {
        $filtered =  $this->getCompetitions()->filter(function (Competition $competition) use ($season): bool {
            return $competition->getSeason() === $season;
        });
        $first = $filtered->first();
        return $first !== false ? $first : null;
    }
}
