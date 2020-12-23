<?php
declare(strict_types=1);

namespace Sports\Competition;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Competition;
use Sports\Sport as SportBase;
use Sports\Competition\Field as CompetitionField;
use SportsHelpers\Identifiable;
use SportsHelpers\SportConfig;

class Sport extends Identifiable
{
    protected SportBase $sport;
    protected Competition $competition;
    /**
     * @var ArrayCollection|CompetitionField[]
     */
    protected $fields;

    public function __construct(SportBase $sport, Competition $competition)
    {
        $this->sport = $sport;
        $this->competition = $competition;
        $this->competition->getSports()->add($this);
        $this->fields = new ArrayCollection();
    }

    public function getSport(): SportBase
    {
        return $this->sport;
    }

    public function getCompetition(): Competition
    {
        return $this->competition;
    }

    /**
     * @return ArrayCollection | Field[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    public function getField(int $priority): ?Field
    {
        $fields = array_filter(
            $this->getFields()->toArray(),
            function (Field $field) use ($priority): bool {
                return $field->getPriority() === $priority;
            }
        );
        return count($fields) > 0 ? array_shift($fields) : null;
    }

    public function createConfig(): SportConfig
    {
        return new SportConfig(
            $this->getSport(),
            $this->fields->count(),
            $this->getSport()->getNrOfGamePlaces()
        );
    }
}