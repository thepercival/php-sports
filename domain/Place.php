<?php

declare(strict_types=1);

namespace Sports;

use Exception;
use InvalidArgumentException;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Competitor\StartLocation;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Game\State;
use Sports\Game\State as GameState;
use Sports\Game\Together as TogetherGame;
use SportsHelpers\Identifiable;
use SportsHelpers\PlaceLocationInterface;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Target as QualifyTarget;

class Place extends Identifiable implements PlaceLocationInterface
{
    private int $placeNr;

    protected string|null $name = null;
    protected int $extraPoints;
    protected Place|null $qualifiedPlace = null;

    public const MAX_LENGTH_NAME = 10;

    public function __construct(protected Poule $poule, int $number = null)
    {
        if ($number === null) {
            $number = $poule->getPlaces()->count() + 1;
        }
        $this->placeNr = $number;

        if (!$poule->getPlaces()->contains($this)) {
            $poule->getPlaces()->add($this) ;
        }
        $this->setExtraPoints(0);
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function getPouleNr(): int
    {
        return $this->poule->getNumber();
    }

    public function getPlaceNr(): int
    {
        return $this->placeNr;
    }

    public function getRound(): Round
    {
        return $this->getPoule()->getRound();
    }

    public function getExtraPoints(): int
    {
        return $this->extraPoints;
    }

    public final function setExtraPoints(int $extraPoints): void
    {
        $this->extraPoints = $extraPoints;
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

    private function getHorizontalNumber(QualifyTarget $qualifyTarget): int
    {
        if ($qualifyTarget === QualifyTarget::Winners) {
            return $this->getPlaceNr();
        }
        return $this->getPoule()->getPlaces()->count() + 1 - $this->getPlaceNr();
    }

    public function getHorizontalPoule(QualifyTarget $qualifyTarget): HorizontalPoule
    {
        return $this->getRound()->getHorizontalPoule($qualifyTarget, $this->getHorizontalNumber($qualifyTarget));
    }

    /**
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGames(CompetitionSport|null $competitionSport = null): array
    {
        return array_values(array_filter(
            $this->getPoule()->getGames($competitionSport),
            function (AgainstGame|TogetherGame $game): bool {
                return $game->isParticipating($this);
            }
        ));
    }

    /**
     * @param CompetitionSport|null $competitionSport
     * @return list<AgainstGame>
     */
    public function getAgainstGames(CompetitionSport|null $competitionSport = null): array
    {
        return array_values(
            $this->getPoule()->getAgainstGames()->filter(function (AgainstGame $game) use ($competitionSport): bool {
                return $game->isParticipating($this)
                    && ($competitionSport === null || $competitionSport === $game->getCompetitionSport());
            })->toArray()
        );
    }

    /**
     * @param CompetitionSport|null $competitionSport
     * @return list<TogetherGame>
     */
    public function getTogetherGames(CompetitionSport|null $competitionSport = null): array
    {
        return array_values(
            $this->getPoule()->getTogetherGames()->filter(function (TogetherGame $game) use ($competitionSport): bool {
                return $game->isParticipating($this)
                    && ($competitionSport === null || $competitionSport === $game->getCompetitionSport());
            })->toArray()
        );
    }

    /**
     * @param CompetitionSport|null $competitionSport
     * @return list<TogetherGamePlace>
     * @throws Exception
     */
    public function getTogetherGamePlaces(CompetitionSport|null $competitionSport = null): array
    {
        return array_map(
            function (TogetherGame $game): TogetherGamePlace {
                foreach ($game->getPlaces() as $gamePlace) {
                    if ($gamePlace->getPlace() === $this) {
                        return $gamePlace;
                    }
                }
                throw new Exception('place should be in own games', E_ERROR);
            },
            $this->getTogetherGames($competitionSport)
        );
    }

    public function getQualifiedPlace(): Place|null
    {
        return $this->qualifiedPlace;
    }

    public function setQualifiedPlace(Place|null $place): void
    {
        $this->qualifiedPlace = $place;
    }

    public function getStartLocation(): StartLocation|null
    {
        if ($this->qualifiedPlace === null) {
            if ($this->getRound()->isRoot()) {
                return new StartLocation(
                    $this->getRound()->getCategory()->getNumber(),
                    $this->getPouleNr(),
                    $this->getPlaceNr()
                );
            }
            return null;
        }
        return $this->qualifiedPlace->getStartLocation();
    }

    public function getStructureLocation(): string
    {
        return $this->poule->getStructureLocation() . '.' . $this->getPlaceNr();
    }

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

    public function getUniqueIndex(): string
    {
        return $this->getPouleNr() . '.' . $this->getPlaceNr();
    }
}
