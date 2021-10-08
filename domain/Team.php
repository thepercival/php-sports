<?php
declare(strict_types=1);

namespace Sports;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Team\Player;
use SportsHelpers\Identifiable;
use Doctrine\ORM\PersistentCollection;

class Team extends Identifiable
{
    protected Association $association;
    protected string $name;
    protected string|null $abbreviation = null;
    protected string|null $imageUrl = null;
    protected string|null $countryCode = null;
    /**
     * @var ArrayCollection<int|string, Player>|PersistentCollection<int|string, Player>
     */
    protected ArrayCollection|PersistentCollection $players;

    const MIN_LENGTH_NAME = 2;
    const MAX_LENGTH_NAME = 30;
    const MAX_LENGTH_ABBREVIATION = 3;
    const MAX_LENGTH_IMAGEURL = 150;

    // Every team should must have a club, a association or a country
    const TYPE_ASSOCIATION = 1;
    const TYPE_COUNTRY = 2;
    const TYPE_CLUB = 4;

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

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl = null): void
    {
        if ($imageUrl !== null && strlen($imageUrl) === 0) {
            $imageUrl = null;
        }

        if ($imageUrl !== null &&  strlen($imageUrl) > self::MAX_LENGTH_IMAGEURL) {
            throw new \InvalidArgumentException("de imageUrl mag maximaal ".self::MAX_LENGTH_IMAGEURL." karakters bevatten", E_ERROR);
        }
        $this->imageUrl = $imageUrl;
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
     * @return ArrayCollection<int|string, Player>|PersistentCollection<int|string, Player>
     */
    public function getPlayers(): ArrayCollection|PersistentCollection
    {
        return $this->players;
    }
}
