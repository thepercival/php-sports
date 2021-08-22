<?php
declare(strict_types=1);

namespace Sports\Competition;

use Sports\Priority\Prioritizable;
use Sports\Competition;
use Sports\Competition\Sport as CompetitionSport;
use SportsHelpers\Identifiable;

class Field extends Identifiable implements Prioritizable
{
    protected int $priority;
    private string|null $name = null;

    const MIN_LENGTH_NAME = 1;
    const MAX_LENGTH_NAME = 3;

    public function __construct(private CompetitionSport $competitionSport, int $priority = null)
    {
        $this->competitionSport->getFields()->add($this);
        if ($priority === null || $priority === 0) {
            $priority = count($this->competitionSport->getFields());
        }
        $this->setPriority($priority);
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function setName(string|null $name): void
    {
        if ($name !== null && (strlen($name) < self::MIN_LENGTH_NAME or strlen($name) > self::MAX_LENGTH_NAME)) {
            throw new \InvalidArgumentException("de naam moet minimaal ".self::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".self::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
        }

        $this->name = $name;
    }

    public function getCompetitionSport(): CompetitionSport
    {
        return $this->competitionSport;
    }

    public function getCompetition(): Competition
    {
        return $this->competitionSport->getCompetition();
    }
}
