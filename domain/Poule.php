<?php
declare(strict_types=1);

namespace Sports;

use \Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use InvalidArgumentException;
use SportsHelpers\Identifiable;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Together as TogetherGame;
use Sports\Competition\Sport as CompetitionSport;

class Poule extends Identifiable
{
    protected int $number;
    protected string|null $name = null;
    /**
     * @phpstan-var ArrayCollection<int|string, Place>|PersistentCollection<int|string, Place>
     * @psalm-var ArrayCollection<int|string, Place>
     */
    protected ArrayCollection|PersistentCollection $places;
    /**
     * @phpstan-var ArrayCollection<int|string, AgainstGame>|PersistentCollection<int|string, AgainstGame>
     * @psalm-var ArrayCollection<int|string, AgainstGame>
     */
    protected ArrayCollection|PersistentCollection $againstGames;
    /**
     * @phpstan-var ArrayCollection<int|string, TogetherGame>|PersistentCollection<int|string, TogetherGame>
     * @psalm-var ArrayCollection<int|string, TogetherGame>
     */
    protected ArrayCollection|PersistentCollection $togetherGames;

    const MAX_LENGTH_NAME = 10;

    public function __construct(protected Round $round, int $number = null)
    {
        if ($number === null) {
            $number = $round->getPoules()->count() + 1;
        }
        $this->number = $number;
        if (!$round->getPoules()->contains($this)) {
            $round->getPoules()->add($this) ;
        }
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

    public function getNumber(): int
    {
        return $this->number;
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
                throw new InvalidArgumentException(
                    "de naam mag maximaal " . self::MAX_LENGTH_NAME . " karakters bevatten",
                    E_ERROR
                );
            }
            if (preg_match('/[^a-z0-9 ]/i', $name) === 1) {
                throw new InvalidArgumentException("de naam mag alleen cijfers, letters en spaties bevatten", E_ERROR);
            }
        }
        $this->name = $name;
    }

    public function getStructureLocation(): string
    {
        return $this->getRound()->getStructurePathNode()->pathToString() . '.' . $this->getNumber();
    }

    /**
     * @phpstan-return ArrayCollection<int|string, Place>|PersistentCollection<int|string, Place>
     * @psalm-return ArrayCollection<int|string, Place>
     */
    public function getPlaces(): ArrayCollection|PersistentCollection
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
            return $place->getPlaceNr() === $number;
        });
        $place = reset($places);
        if ($place === false) {
            throw new \Exception('de pouleplek kan niet gevonden worden', E_ERROR);
        }
        return $place;
    }

    /**
     * @param CompetitionSport|null $competitionSport
     * @return array<int|string, AgainstGame|TogetherGame>
     */
    public function getGames(CompetitionSport|null $competitionSport = null): array
    {
        $games = array_merge($this->againstGames->toArray(), $this->togetherGames->toArray());
        if ($competitionSport !== null) {
            return array_filter($games, fn (AgainstGame|TogetherGame $game) => $game->getCompetitionSport() === $competitionSport);
        }
        return $games;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, AgainstGame>|PersistentCollection<int|string, AgainstGame>
     * @psalm-return ArrayCollection<int|string, AgainstGame>
     */
    public function getAgainstGames(): ArrayCollection|PersistentCollection
    {
        return $this->againstGames;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, TogetherGame>|PersistentCollection<int|string, TogetherGame>
     * @psalm-return ArrayCollection<int|string, TogetherGame>
     */
    public function getTogetherGames(): ArrayCollection|PersistentCollection
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

    /*public function getNrOfGamesPerRoundNumber(SportVariant $sportVariant): int
    {
        $nrOfPlaces = $this->getPlaces()->count();
        $rest = $nrOfPlaces % $sportVariant->getNrOfGamePlaces();
        return (int)(($nrOfPlaces - $rest) / $sportVariant->getNrOfGamePlaces());
    }*/

    public function getState(CompetitionSport|null $competitionSport = null): int
    {
        $allPlayed = true;
        $games = $this->getGames($competitionSport);
        foreach ($games as $game) {
            if ($game->getState() !== State::Finished) {
                $allPlayed = false;
                break;
            }
        }
        if (count($games) > 0 && $allPlayed) {
            return State::Finished;
        }
        foreach ($games as $game) {
            if ($game->getState() !== State::Created) {
                return State::InProgress;
            }
        }
        return State::Created;
    }
}
