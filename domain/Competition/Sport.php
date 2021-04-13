<?php
declare(strict_types=1);

namespace Sports\Competition;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Competition;
use Sports\Sport as SportsSport;
use SportsHelpers\Identifiable;
use SportsHelpers\Sport\GameAmountVariant;
use SportsHelpers\Sport\Variant;

class Sport extends Identifiable implements Variant
{
    /**
     * @var ArrayCollection<int|string,Field>
     */
    protected $fields;

    public function __construct(
        protected SportsSport $sport,
        protected Competition $competition,
        protected int $nrOfGamePlaces,
        protected int $gameMode
    ) {
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

    public function getGameMode(): int
    {
        return $this->gameMode;
    }

    public function getNrOfGamePlaces(): int
    {
        return $this->nrOfGamePlaces;
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

    public function createGameAmountVariant(int $gameAmount): GameAmountVariant
    {
        return new GameAmountVariant(
            $this->getGameMode(),
            $this->getNrOfGamePlaces(),
            $this->fields->count(),
            $gameAmount
        );
    }
}
