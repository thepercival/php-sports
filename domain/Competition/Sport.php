<?php

declare(strict_types=1);

namespace Sports\Competition;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Competition;
use Sports\Ranking\PointsCalculation;
use Sports\Sport as SportsSport;
use SportsHelpers\GameMode;
use SportsHelpers\Sport\PersistVariant;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;

class Sport extends PersistVariant implements \Stringable
{
    /**
     * @var Collection<int|string,Field>
     */
    protected $fields;
    private PointsCalculation $defaultPointsCalculation;

    public function __construct(
        protected SportsSport $sport,
        protected Competition $competition,
        PointsCalculation $defaultPointsCalculation,
        PersistVariant $sportVariant
    ) {
        parent::__construct(
            $sportVariant->getGameMode(),
            $sportVariant->getNrOfHomePlaces(),
            $sportVariant->getNrOfAwayPlaces(),
            $sportVariant->getNrOfGamePlaces(),
            $sportVariant->getNrOfH2H(),
            $sportVariant->getNrOfGamesPerPlace()
        );
        $this->defaultPointsCalculation =
            $sportVariant->getGameMode() === GameMode::Against ? $defaultPointsCalculation : PointsCalculation::Scores;

        $this->competition->getSports()->add($this);
        $this->fields = new ArrayCollection();
    }

    public function getDefaultPointsCalculation(): PointsCalculation
    {
        return $this->defaultPointsCalculation;
    }

    public function getDefaultPointsCalculationNative(): int
    {
        return $this->defaultPointsCalculation->value;
    }

    public function setDefaultPointsCalculationNative(int $defaultPointsCalculation): void
    {
        $this->defaultPointsCalculation = PointsCalculation::from($defaultPointsCalculation);
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
     * @return Collection<int|string,Field>
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

    public function convertAgainst(): void
    {
        if ($this->getNrOfH2H() > 0) {
            $this->nrOfH2H = 0;
            $this->nrOfGamesPerPlace = 1;
        } else {
            $this->nrOfH2H = 1;
            $this->nrOfGamesPerPlace = 0;
        }
    }

    public function __toString(): string
    {
        return $this->createVariant() . ' f=>' . $this->getFields()->count();
    }
}
