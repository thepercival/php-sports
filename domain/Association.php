<?php
declare(strict_types=1);

namespace Sports;

use \Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use SportsHelpers\Identifiable;

class Association extends Identifiable
{
    protected string $name;
    protected string|null $description = null;
    protected string|null $countryCode = null;
    protected Association|null $parent = null;
    /**
     * @phpstan-var ArrayCollection<int|string, Association>|PersistentCollection<int|string, Association>
     * @psalm-var ArrayCollection<int|string, Association>
     */
    protected ArrayCollection|PersistentCollection $children;
    /**
     * @phpstan-var ArrayCollection<int|string, League>|PersistentCollection<int|string, League>
     * @psalm-var ArrayCollection<int|string, League>
     */
    protected ArrayCollection|PersistentCollection $leagues;
    /**
     * @phpstan-var ArrayCollection<int|string, Team>|PersistentCollection<int|string, Team>
     * @psalm-var ArrayCollection<int|string, Team>
     */
    protected ArrayCollection|PersistentCollection $teams;

    const MIN_LENGTH_NAME = 2;
    const MAX_LENGTH_NAME = 30;
    const MAX_LENGTH_DESCRIPTION = 50;

    public function __construct(string $name)
    {
        $this->setName($name);
        $this->children = new ArrayCollection();
        $this->leagues = new ArrayCollection();
        $this->teams = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        if (strlen($name) < self::MIN_LENGTH_NAME or strlen($name) > self::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException(
                "de naam moet minimaal " . self::MIN_LENGTH_NAME . " karakters bevatten en mag maximaal " . self::MAX_LENGTH_NAME . " karakters bevatten",
                E_ERROR
            );
        }

        $this->name = $name;
    }

    public function getDescription(): string|null
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(string|null $description = null): void
    {
        if ($description !== null && strlen($description) === 0) {
            $description = null;
        }
        if ($description !== null && strlen($description) > self::MAX_LENGTH_DESCRIPTION) {
            throw new \InvalidArgumentException(
                "de omschrijving mag maximaal " . self::MAX_LENGTH_DESCRIPTION . " karakters bevatten",
                E_ERROR
            );
        }
        $this->description = $description;
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

    public function getParent(): ?Association
    {
        return $this->parent;
    }

    /**
     * @param Association|null $parent
     * @throws \Exception
     * @return void
     */
    public function setParent(Association $parent = null): void
    {
        if ($parent === $this) {
            throw new \Exception("de parent-bond mag niet zichzelf zijn", E_ERROR);
        }
        if ($this->parent !== null) {
            $this->parent->getChildren()->removeElement($this);
        }
        $this->parent = $parent;
        if ($this->parent !== null) {
            $this->parent->getChildren()->add($this);
        }
    }

    // In case the object is not created with the constructor, children can be null
    /**
     * @phpstan-return ArrayCollection<int|string, Association>|PersistentCollection<int|string, Association>
     * @psalm-return ArrayCollection<int|string, Association>
     */
    public function getChildren(): ArrayCollection|PersistentCollection
    {
        return $this->children;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, League>|PersistentCollection<int|string, League>
     * @psalm-return ArrayCollection<int|string, League>
     */
    public function getLeagues(): ArrayCollection|PersistentCollection
    {
        return $this->leagues;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, Team>|PersistentCollection<int|string, Team>
     * @psalm-return ArrayCollection<int|string, Team>
     */
    public function getTeams(): ArrayCollection|PersistentCollection
    {
        return $this->teams;
    }
}
