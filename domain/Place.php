<?php

namespace Sports;

use InvalidArgumentException;
use Sports\Place\Location as PlaceLocation;
use Sports\Place\LocationBase as PlaceLocationBase;
use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Poule\Horizontal as HorizontalPoule;
use SportsHelpers\Identifiable;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Together as TogetherGame;

class Place extends Identifiable implements PlaceLocation
{
    /**
     * @var string|null
     */
    protected $name;
    /**
     * @var int
     */
    protected $number;
    protected string|null $roundLocationId = null;
    /**
     * @var int
     */
    protected $penaltyPoints;
    protected SingleQualifyRule|MultipleQualifyRule|null $fromQualifyRule;
    /**
     * @var array<SingleQualifyRule|MultipleQualifyRule>
     */
    protected array $toQualifyRules = [];
    /**
     * @var HorizontalPoule
     */
    protected $horizontalPouleWinners;
    /**
     * @var HorizontalPoule
     */
    protected $horizontalPouleLosers;
    /**
     * @var Place | null
     */
    protected $qualifiedPlace;

    protected $competitorDep;

    const MAX_LENGTH_NAME = 10;

    public function __construct(protected Poule $poule, int $number = null)
    {
        if ($number === null) {
            $number = $poule->getPlaces()->count() + 1;
        }
        $this->setPoule($poule);
        $this->setNumber($number);
        $this->setPenaltyPoints(0);
    }

    /**
     * @return Poule
     */
    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function setPoule(Poule $poule): void
    {
        if (/*$this->poule !== null &&*/ $this->poule->getPlaces()->contains($this)) {
            $this->poule->getPlaces()->removeElement($this);
        }
        if (!$poule->getPlaces()->contains($this)) {
            $poule->getPlaces()->add($this) ;
        }
        $this->poule = $poule;
    }

    public function getRound(): Round
    {
        return $this->getPoule()->getRound();
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    public function getPouleNr(): int
    {
        return $this->getPoule()->getNumber();
    }

    public function getPlaceNr(): int
    {
        return $this->getNumber();
    }

    /**
     * @return int
     */
    public function getPenaltyPoints()
    {
        return $this->penaltyPoints;
    }

    /**
     * @param int $penaltyPoints
     *
     * @return void
     */
    public function setPenaltyPoints(int $penaltyPoints): void
    {
        $this->penaltyPoints = $penaltyPoints;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name = null): void
    {
        if (is_string($name) and strlen($name) === 0) {
            $name = null;
        }

        if (strlen($name) > static::MAX_LENGTH_NAME) {
            throw new InvalidArgumentException("de naam mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
        }

        if (preg_match('/[^a-z0-9 ]/i', $name)) {
            throw new InvalidArgumentException("de naam mag alleen cijfers, letters en spaties bevatten", E_ERROR);
        }

        $this->name = $name;
    }

    public function getFromQualifyRule(): SingleQualifyRule|MultipleQualifyRule|null
    {
        return $this->fromQualifyRule;
    }

    public function setFromQualifyRule(SingleQualifyRule|MultipleQualifyRule|null $qualifyRule): void
    {
        $this->fromQualifyRule = $qualifyRule;
    }

    /**
     * @return array<int|string,SingleQualifyRule|MultipleQualifyRule>
     */
    public function &getToQualifyRules(): array
    {
        return $this->toQualifyRules;
    }

    /**
     * @return MultipleQualifyRule|SingleQualifyRule|null
     */
    public function getToQualifyRule(int $winnersOrLosers)
    {
        $filtered = array_filter($this->toQualifyRules, function ($qualifyRule) use ($winnersOrLosers): bool {
            return ($qualifyRule->getWinnersOrLosers() === $winnersOrLosers);
        });
        $toQualifyRule = reset($filtered);
        return $toQualifyRule !== false ? $toQualifyRule : null;
    }

    public function setToQualifyRule(int $winnersOrLosers, SingleQualifyRule|MultipleQualifyRule|null $qualifyRule): void
    {
        $originalToQualifyRule = $this->getToQualifyRule($winnersOrLosers);
        if ($originalToQualifyRule !== null) {
            if (($key = array_search($originalToQualifyRule, $this->toQualifyRules, true)) !== false) {
                unset($this->toQualifyRules[$key]);
            }
        }
        if ($qualifyRule !== null) {
            $this->toQualifyRules[] = $qualifyRule;
        }
    }

    public function getHorizontalPoule(int $winnersOrLosers): HorizontalPoule
    {
        return ($winnersOrLosers === QualifyGroup::WINNERS) ? $this->horizontalPouleWinners : $this->horizontalPouleLosers;
    }

    public function setHorizontalPoule(int $winnersOrLosers, ?HorizontalPoule $horizontalPoule): void
    {
        if ($winnersOrLosers === QualifyGroup::WINNERS) {
            $this->horizontalPouleWinners = $horizontalPoule;
        } else {
            $this->horizontalPouleLosers = $horizontalPoule;
        }
        if ($horizontalPoule !== null) {
            $places = &$horizontalPoule->getPlaces();
            $places[] = $this;
        }
    }

    public function getRoundLocationId(): string
    {
        if ($this->roundLocationId === null) {
            $this->roundLocationId = $this->getPoule()->getNumber() . '.' . $this->getNumber();
        }
        return $this->roundLocationId;
    }

    public function getStructureNumber(): string
    {
        return $this->getPoule()->getStructureNumber() . '.' . $this->number;
    }

    /**
     * @return array<AgainstGame|TogetherGame>
     */
    public function getGames(): array
    {
        return array_filter(
            $this->getPoule()->getGames(),
            function (AgainstGame|TogetherGame $game): bool {
                return $game->isParticipating($this);
            }
        );
    }

    public function getQualifiedPlace(): ?Place
    {
        return $this->qualifiedPlace;
    }

    public function setQualifiedPlace(Place $place = null): void
    {
        $this->qualifiedPlace = $place;
    }

    public function getQualifiedPlaceLocation(): ?PlaceLocationBase
    {
        if ($this->qualifiedPlace === null) {
            return null;
        }
        return new PlaceLocationBase(
            $this->qualifiedPlace->getPoule()->getNumber(),
            $this->qualifiedPlace->getNumber()
        );
    }

    public function getStartLocation(): PlaceLocation
    {
        if ($this->qualifiedPlace === null) {
            return $this;
        }
        return $this->qualifiedPlace->getStartLocation();
    }
}
