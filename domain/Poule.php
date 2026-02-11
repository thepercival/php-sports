<?php

declare(strict_types=1);

namespace Sports;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use InvalidArgumentException;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Game\Against as AgainstGame;
use Sports\Game\State as GameState;
use Sports\Game\Together as TogetherGame;
use Sports\Structure\Locations\StructureLocationPoule;
use SportsHelpers\Identifiable;

/**
 * @api
 */
class Poule extends Identifiable
{
    protected int $number;
    protected string|null $name = null;
    /**
     * @var Collection<int|string, Place>
     */
    protected Collection $places;
    /**
     * @var Collection<int|string, AgainstGame>
     */
    protected Collection $againstGames;
    /**
     * @var Collection<int|string, TogetherGame>
     */
    protected Collection $togetherGames;

    protected string|null $categoryLocation = null;

    public const int MAX_LENGTH_NAME = 10;

    public function __construct(protected Round $round, int|null $number = null)
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

    public function setName(string|null $name = null): void
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

    public function getStructureLocation(): StructureLocationPoule
    {
        return new StructureLocationPoule(
            $this->round->getCategory()->getNumber(),
            $this->round->getStructurePathNode(),
            $this->getNumber()
        );
    }

    /**
     * @return Collection<int|string, Place>
     */
    public function getPlaces(): Collection
    {
        return $this->places;
    }

    /**
     * @param Collection<int|string,Place> $places
     *
     * @return void
     */
    public function setPlaces(Collection $places): void
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
     * @return Collection<int|string, AgainstGame>
     */
    public function getAgainstGames(): Collection
    {
        return $this->againstGames;
    }

    /**
     * @return Collection<int|string, TogetherGame>
     */
    public function getTogetherGames(): Collection
    {
        return $this->togetherGames;
    }

    /**
     * @param GameState $state
     * @return array<int|string, AgainstGame|TogetherGame>
     */
    public function getGamesWithState(GameState $state): array
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

    public function getGamesState(CompetitionSport|null $competitionSport = null): GameState
    {
        $allPlayed = true;
        $games = $this->getGames($competitionSport);
        foreach ($games as $game) {
            if ($game->getState() !== GameState::Finished) {
                $allPlayed = false;
                break;
            }
        }
        if (count($games) > 0 && $allPlayed) {
            return GameState::Finished;
        }
        foreach ($games as $game) {
            if ($game->getState() !== GameState::Created) {
                return GameState::InProgress;
            }
        }
        return GameState::Created;
    }
}
