<?php

namespace Sports;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Team\Player;
use SportsHelpers\Identifiable;

class Team implements Identifiable
{
    /**
     * @var int|string
     */
    protected $id;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $abbreviation;
    /**
     * @var string
     */
    protected $imageUrl;
    /**
     * @var Association
     */
    protected $association;
    /**
     * @var string
     */
    protected $countryCode;
    /**
     * @var ArrayCollection|Player[]
     */
    protected $players;

    const MIN_LENGTH_NAME = 2;
    const MAX_LENGTH_NAME = 30;
    const MAX_LENGTH_ABBREVIATION = 3;
    const MAX_LENGTH_IMAGEURL = 150;

    // Every team should must have a club, a association or a country
    CONST TYPE_ASSOCIATION = 1;
    CONST TYPE_COUNTRY = 2;
    CONST TYPE_CLUB = 4;

    public function __construct(Association $association, string $name)
    {
        $this->setAssociation($association);
        $this->setName($name);
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|string $id
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        if (strlen($name) === 0) {
            throw new \InvalidArgumentException("de naam moet gezet zijn", E_ERROR);
        }

        if (strlen($name) < static::MIN_LENGTH_NAME or strlen($name) > static::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException("de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
        }
        $this->name = $name;
    }

    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    public function setAbbreviation(string $abbreviation = null)
    {
        if (strlen($abbreviation) === 0) {
            $abbreviation = null;
        }

        if (strlen($abbreviation) > static::MAX_LENGTH_ABBREVIATION) {
            throw new \InvalidArgumentException("de afkorting mag maximaal ".static::MAX_LENGTH_ABBREVIATION." karakters bevatten", E_ERROR);
        }
        $this->abbreviation = $abbreviation;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl = null)
    {
        if (strlen($imageUrl) === 0) {
            $imageUrl = null;
        }

        if (strlen($imageUrl) > static::MAX_LENGTH_IMAGEURL) {
            throw new \InvalidArgumentException("de imageUrl mag maximaal ".static::MAX_LENGTH_IMAGEURL." karakters bevatten", E_ERROR);
        }
        $this->imageUrl = $imageUrl;
    }

    public function getAssociation(): Association
    {
        return $this->association;
    }

    public function setAssociation(Association $association)
    {
        if ($association->getTeams() !== null and !$association->getTeams()->contains($this)) {
            $association->getTeams()->add($this) ;
        }
        $this->association = $association;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode)
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
     * @return Player[] | ArrayCollection
     */
    public function getPlayers()
    {
        return $this->players;
    }
}
