<?php
declare(strict_types=1);

namespace Sports\Competition;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Competition;
use Sports\Sport as SportsSport;
use SportsHelpers\Sport\PersistVariant;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;

class Sport extends PersistVariant implements \Stringable
{
    /**
     * @var ArrayCollection<int|string,Field>
     */
    protected $fields;

    public function __construct(
        protected SportsSport $sport,
        protected Competition $competition,
        PersistVariant $sportVariant
    ) {
        parent::__construct(
            $sportVariant->getGameMode(),
            $sportVariant->getNrOfHomePlaces(),
            $sportVariant->getNrOfAwayPlaces(),
            $sportVariant->getNrOfH2H(),
            $sportVariant->getNrOfPartials(),
            $sportVariant->getNrOfGamePlaces(),
            $sportVariant->getNrOfGamesPerPlace()
        );

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

    public function createVariantWithFields(): SportVariantWithFields
    {
        return new SportVariantWithFields($this->createVariant(), $this->getFields()->count());
    }

    public function __toString(): string
    {
        return $this->createVariant() . ' f(' . $this->getFields()->count() . ')';
    }
}
