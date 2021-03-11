<?php

namespace Sports;

use \Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use SportsHelpers\GameMode;
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
     * @var Place[] | ArrayCollection
     */
    protected $places;
    /**
     * @var AgainstGame[] | ArrayCollection
     */
    protected $againstGames;
    /**
     * @var TogetherGame[] | ArrayCollection
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

    /**
     * @return Round
     */
    public function getRound()
    {
        return $this->round;
    }

    /**
     * @param Round $round
     */
    protected function setRound(Round $round)
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

    public function setNumber(int $number)
    {
        $this->number = $number;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name = null)
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

    public function getStructureNumber(): int
    {
        return $this->structureNumber;
    }

    public function setStructureNumber(int $structureNumber): void
    {
        $this->structureNumber = $structureNumber;
    }

    /**
     * @return Place[] | ArrayCollection
     */
    public function getPlaces()
    {
        return $this->places;
    }

    /**
     * @param Place[] | ArrayCollection $places
     */
    public function setPlaces($places)
    {
        $this->places = $places;
    }

    /**
     * @return ?Place
     */
    public function getPlace($number): ?Place
    {
        $places = array_filter($this->getPlaces()->toArray(), function ($place) use ($number): bool {
            return $place->getNumber() === $number;
        });
        return array_shift($places);
    }

    /**
     * @return AgainstGame[] | TogetherGame[] | array
     */
    public function getGames(): array
    {
        return array_merge( $this->againstGames->toArray(), $this->togetherGames->toArray() );
    }

    /**
     * @return AgainstGame[] |  ArrayCollection
     */
    public function getAgainstGames()
    {
        return $this->againstGames;
    }

    /**
     * @return TogetherGame[] |  ArrayCollection
     */
    public function getTogetherGames()
    {
        return $this->togetherGames;
    }

    /**
     * @param int $state
     * @return AgainstGame[] |  TogetherGame[] | array
     */
    public function getGamesWithState(int $state)
    {
        return array_filter($this->getGames(), function (AgainstGame|TogetherGame $gameIt) use ($state): bool {
            return $gameIt->getState() === $state;
        });
    }

    /**
     * @return bool
     */
    public function needsRanking()
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
