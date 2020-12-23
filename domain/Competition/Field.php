<?php

namespace Sports\Competition;

use Sports\Priority\Prioritizable;
use Sports\Sport;
use Sports\Sport\ConfigDep as SportConfig;
use Sports\Competition;
use Sports\Competition\Sport as CompetitionSport;
use SportsHelpers\Identifiable;

class Field extends Identifiable implements Prioritizable
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var int
     */
    protected $priority;
    /**
     * @var CompetitionSport
     */
    private $competitionSport;

    const MIN_LENGTH_NAME = 1;
    const MAX_LENGTH_NAME = 3;

    public function __construct(CompetitionSport $competitionSport, int $priority = null)
    {
        $this->competitionSport = $competitionSport;
        $this->competitionSport->getFields()->add($this);

        if ($priority === null || $priority === 0) {
            $priority = count($this->getCompetition()->getFields());
        }
        $this->setPriority($priority);
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

    public function getCompetitionSport(): CompetitionSport
    {
        return $this->competitionSport;
    }

    public function getCompetition(): Competition
    {
        return $this->getCompetition();
    }


}
