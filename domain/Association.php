<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */

namespace Sports;

use \Doctrine\Common\Collections\ArrayCollection;
use SportsHelpers\Identifiable;

class Association implements Identifiable
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
    protected $description;
    /**
     * @var Association|null
     */
    protected $parent;
    /**
     * @var ArrayCollection
     */
    protected $children;
    /**
     * @var League[] | ArrayCollection
     */
    protected $leagues;
    /**
     * @var Team[] | ArrayCollection
     */
    protected $teams;

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
        if (strlen($name) < static::MIN_LENGTH_NAME or strlen($name) > static::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException(
                "de naam moet minimaal " . static::MIN_LENGTH_NAME . " karakters bevatten en mag maximaal " . static::MAX_LENGTH_NAME . " karakters bevatten",
                E_ERROR
            );
        }

        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description = null)
    {
        if (strlen($description) === 0 && $description !== null) {
            $description = null;
        }
        if (strlen($description) > static::MAX_LENGTH_DESCRIPTION) {
            throw new \InvalidArgumentException(
                "de omschrijving mag maximaal " . static::MAX_LENGTH_DESCRIPTION . " karakters bevatten",
                E_ERROR
            );
        }
        $this->description = $description;
    }

    public function getParent(): ?Association
    {
        return $this->parent;
    }

    /**
     * @param Association|null $parent
     * @throws \Exception
     */
    public function setParent(Association $parent = null)
    {
        if ($parent === $this) {
            throw new \Exception("de parent-bond mag niet zichzelf zijn", E_ERROR);
        }
        if ($this->parent !== null) {
            $this->parent->getChildren()->removeElement($this);
        }
        $this->parent = $parent;
        if ($this->parent !== null && $this->parent->getChildren() !== null) {
            $this->parent->getChildren()->add($this);
        }
    }

    /**
     * In case the object is not created with the constructor, children can be null
     *
     * @return ArrayCollection|null
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return League[] | ArrayCollection
     */
    public function getLeagues()
    {
        return $this->leagues;
    }

    /**
     * @return Team[] | ArrayCollection | null
     */
    public function getTeams()
    {
        return $this->teams;
    }
}
