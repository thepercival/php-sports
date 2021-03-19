<?php
declare(strict_types=1);

namespace Sports\Competition;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Competition;
use Sports\Sport as SportsSport;
use SportsHelpers\Identifiable;
use SportsHelpers\SportConfig;

class Sport extends Identifiable
{
    /**
     * @var ArrayCollection<int|string,Field>
     */
    protected $fields;

    public function __construct(protected SportsSport $sport, protected Competition $competition)
    {
        $this->competition->getSports()->add($this);
        $this->fields = new ArrayCollection();
    }

    public function getSport(): SportsSport
    {
        return $this->sport;
    }

    public function getCompetition(): Competition
    {
        return $this->competition;
    }

    /**
     * @return ArrayCollection<int|string,Field>
     */
    public function getFields()
    {
        return $this->fields;
    }

    public function getField(int $priority): Field
    {
        $fields = array_filter(
            $this->getFields()->toArray(),
            function (Field $field) use ($priority): bool {
                return $field->getPriority() === $priority;
            }
        );
        $field = reset($fields);
        if ($field === false) {
            throw new \Exception('het veld kan niet gevonden worden', E_ERROR);
        }
        return $field;
    }

    public function createConfig(int $gameAmount): SportConfig
    {
        return new SportConfig(
            $this->getSport()->getGameMode(),
            $this->getSport()->getNrOfGamePlaces(),
            $this->fields->count(),
            $gameAmount
        );
    }
}
