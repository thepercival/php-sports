<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-10-17
 * Time: 22:16
 */

namespace Sports;

use Sports\Priority\Prioritizable;
use Sports\Sport;
use Sports\Sport\Config as SportConfig;
use Sports\Competition;

class Field implements Prioritizable
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $name;
    /**
     * @var int
     */
    protected $priority;
    /**
     * @var SportConfig
     */
    private $sportConfig;

    const MIN_LENGTH_NAME = 1;
    const MAX_LENGTH_NAME = 3;

    public function __construct(SportConfig $sportConfig, int $priority = null)
    {
        $this->sportConfig = $sportConfig;
        $this->sportConfig->getFields()->add($this);

        if ($priority === null || $priority === 0) {
            $priority = count($this->getCompetition()->getFields());
        }
        $this->setPriority($priority);
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id = null)
    {
        $this->id = $id;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        if (strlen($name) < static::MIN_LENGTH_NAME or strlen($name) > static::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException("de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
        }

        $this->name = $name;
    }

    public function getSportConfig(): SportConfig
    {
        return $this->sportConfig;
    }

    public function getCompetition(): Competition
    {
        return $this->getSportConfig()->getCompetition();
    }

    public function getSport(): Sport
    {
        return $this->getSportConfig()->getSport();
    }
}
