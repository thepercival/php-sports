<?php

declare(strict_types=1);

namespace Sports;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Team\Player;
use SportsHelpers\Identifiable;

final class Team extends Identifiable
{
    protected Association $association;
    protected string $name;
    protected string|null $abbreviation = null;
    protected string|null $countryCode = null;
    /**
     * @var Collection<int|string, Player>
     */
    protected Collection $players;

    public const int MIN_LENGTH_NAME = 2;
    public const int MAX_LENGTH_NAME = 30;
    public const int MAX_LENGTH_ABBREVIATION = 3;

    // Every team should must have a club, a association or a country
    public const int TYPE_ASSOCIATION = 1;
    public const int TYPE_COUNTRY = 2;
    public const int TYPE_CLUB = 4;

    public function __construct(Association $association, string $name)
    {
        $this->setAssociation($association);
        $this->setName($name);
        $this->players = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        if (strlen($name) === 0) {
            throw new \InvalidArgumentException("de naam moet gezet zijn", E_ERROR);
        }

        if (strlen($name) < self::MIN_LENGTH_NAME or strlen($name) > self::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException("de naam moet minimaal ".self::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".self::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
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
            throw new \InvalidArgumentException("de afkorting mag maximaal ".self::MAX_LENGTH_ABBREVIATION." karakters bevatten", E_ERROR);
        }
        $this->abbreviation = $abbreviation;
    }

    public function getAssociation(): Association
    {
        return $this->association;
    }

    public function setAssociation(Association $association): void
    {
        if (!$association->getTeams()->contains($this)) {
            $association->getTeams()->add($this) ;
        }
        $this->association = $association;
    }

    public function getCountryCode(): string|null
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): void
    {
        if (strlen($countryCode) !== 2) {
            throw new \InvalidArgumentException(
                "country-code niet volgens ISO-3166-1",
                E_ERROR
            );
        }

        $this->countryCode = $countryCode;
    }

    /**
     * @return Collection<int|string, Player>
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

//    public function __toString(): string
//    {
//        return '"' . $this->getName() . ( $this->abbreviation !== null ? ' (' . $this->abbreviation . ')' : '' ) . '"';
//    }
}
