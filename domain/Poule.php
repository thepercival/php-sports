<?php

namespace Sports;

use \Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use SportsHelpers\SportConfig as SportConfig;
use SportsHelpers\Identifiable;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Together as TogetherGame;

class Poule extends Identifiable
{
    protected Round $round;
    protected int $number;
    /**
     * @var string|null
     */
    protected $name;
    /**
     * @var ArrayCollection<int|string,Place>
     */
    protected $places;
    /**
     * @var ArrayCollection<int|string,AgainstGame>
     */
    protected $againstGames;
    /**
     * @var ArrayCollection<int|string,TogetherGame>
     */
    protected $togetherGames;
    protected int $structureNumber = 0;

    const MAX_LENGTH_NAME = 10;

    public function __construct(Round $round, int $number = null)
    {
        if ($number === null) {
            $number = $round->getPoules()->count() + 1;
        }
        $this->setRound($round);
        $this->setNumber($number);
        $this->places = new ArrayCollection();
        $this->againstGames = new ArrayCollection();
        $this->togetherGames = new ArrayCollection();
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    public function getCompetition(): Competition
    {
        return $this->getRound()->getCompetition();
    }

    /**
     * @param Round $round
     *
     * @return void
     */
    protected function setRound(Round $round): void
    {
        if (!$round->getPoules()->contains($this)) {
            $round->getPoules()->add($this) ;
        }
        $this->round = $round;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
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
            if (strlen($name) > static::MAX_LENGTH_NAME) {
                throw new InvalidArgumentException(
                    "de naam mag maximaal " . static::MAX_LENGTH_NAME . " karakters bevatten", E_ERROR
                );
            }
            if (preg_match('/[^a-z0-9 ]/i', $name)) {
                throw new InvalidArgumentException("de naam mag alleen cijfers, letters en spaties bevatten", E_ERROR);
            }
        }
        $this->name = $name;
    }

    public function getStructureNumber(): int
    {
        return $this->structureNumber;
    }

    public function setStructureNumber(int $structureNumber): void
    {
        $this->structureNumber = $structureNumber;
    }

    /**
     * @return ArrayCollection<int|string,Place>
     */
    public function getPlaces(): ArrayCollection
    {
        return $this->places;
    }

    /**
     * @param ArrayCollection<int|string,Place> $places
     *
     * @return void
     */
    public function setPlaces(ArrayCollection $places): void
    {
        $this->places = $places;
    }

    public function getPlace(int $number): Place
    {
        $places = array_filter($this->getPlaces()->toArray(), function (Place $place) use ($number): bool {
            return $place->getNumber() === $number;
        });
        $place = reset($places);
        if ($place === false) {
            throw new \Exception('de pouleplek kan niet gevonden worden', E_ERROR);
        }
        return $place;
    }

    /**
     * @return array<int|string, AgainstGame|TogetherGame>
     */
    public function getGames(): array
    {
        return array_merge($this->againstGames->toArray(), $this->togetherGames->toArray());
    }

    /**
     * @return ArrayCollection<int|string, AgainstGame>
     */
    public function getAgainstGames(): ArrayCollection
    {
        return $this->againstGames;
    }

    /**
     * @return ArrayCollection<int|string, TogetherGame>
     */
    public function getTogetherGames(): ArrayCollection
    {
        return $this->togetherGames;
    }

    /**
     * @param int $state
     * @return array<int|string, AgainstGame|TogetherGame>
     */
    public function getGamesWithState(int $state): array
    {
        return array_filter($this->getGames(), function (AgainstGame|TogetherGame $gameIt) use ($state): bool {
            return $gameIt->getState() === $state;
        });
    }

    public function needsRanking(): bool
    {
        return ($this->getPlaces()->count() > 2);
    }

    public function getNrOfGamesPerRoundNumber(SportConfig $sportConfig): int
    {
        $nrOfPlaces = $this->getPlaces()->count();
        $rest = $nrOfPlaces % $sportConfig->getNrOfGamePlaces();
        return (int)(($nrOfPlaces - $rest) / $sportConfig->getNrOfGamePlaces());
    }

    public function getState(): int
    {
        $allPlayed = true;
        foreach ($this->getGames() as $game) {
            if ($game->getState() !== State::Finished) {
                $allPlayed = false;
                break;
            }
        }
        if (count($this->getGames()) > 0 && $allPlayed) {
            return State::Finished;
        }
        foreach ($this->getGames() as $game) {
            if ($game->getState() !== State::Created) {
                return State::InProgress;
            }
        }
        return State::Created;
    }
}
