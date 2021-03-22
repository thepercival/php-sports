<?php
declare(strict_types=1);

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
    protected string|null $name = null;
    protected int $number;
    protected string|null $roundLocationId = null;
    protected int $penaltyPoints;
    protected SingleQualifyRule|MultipleQualifyRule|null $fromQualifyRule = null;
    /**
     * @var list<SingleQualifyRule|MultipleQualifyRule>
     */
    protected array $toQualifyRules = [];
    protected HorizontalPoule|null $horizontalPouleWinners = null;
    protected HorizontalPoule|null $horizontalPouleLosers = null;
    protected Place|null $qualifiedPlace = null;
    protected Competitor|null $competitorDep = null;

    const MAX_LENGTH_NAME = 10;

    public function __construct(protected Poule $poule, int $number = null)
    {
        if ($number === null) {
            $number = $poule->getPlaces()->count() + 1;
        }
        $this->number = $number;
        if (!$poule->getPlaces()->contains($this)) {
            $poule->getPlaces()->add($this) ;
        }
        $this->setPenaltyPoints(0);
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function getRound(): Round
    {
        return $this->getPoule()->getRound();
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getPouleNr(): int
    {
        return $this->getPoule()->getNumber();
    }

    public function getPlaceNr(): int
    {
        return $this->getNumber();
    }

    public function getPenaltyPoints(): int
    {
        return $this->penaltyPoints;
    }

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
        if ($name !== null and strlen($name) === 0) {
            $name = null;
        }

        if ($name !== null) {
            if (strlen($name) > self::MAX_LENGTH_NAME) {
                throw new InvalidArgumentException("de naam mag maximaal ".self::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
            }
            if (preg_match('/[^a-z0-9 ]/i', $name) === 1) {
                throw new InvalidArgumentException("de naam mag alleen cijfers, letters en spaties bevatten", E_ERROR);
            }
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

    public function getToQualifyRule(int $winnersOrLosers): MultipleQualifyRule|SingleQualifyRule|null
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
            $idx = array_search($originalToQualifyRule, $this->toQualifyRules, true);
            if ($idx !== false) {
                array_splice($this->toQualifyRules, $idx, 1);
            }
        }
        if ($qualifyRule !== null) {
            array_push($this->toQualifyRules, $qualifyRule);
        }
    }

    public function getHorizontalPoule(int $winnersOrLosers): HorizontalPoule|null
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
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGames(): array
    {
        return array_values(array_filter(
            $this->getPoule()->getGames(),
            function (AgainstGame|TogetherGame $game): bool {
                return $game->isParticipating($this);
            }
        ));
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
