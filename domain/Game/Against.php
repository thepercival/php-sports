<?php

namespace Sports\Game;

use DateTimeImmutable;
use \Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Sports\Competitor;
use Sports\Place;
use Sports\Game as GameBase;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Place\Location\Map;
use Sports\Poule;
use Sports\Score\Against as AgainstScore;
use Sports\Competition\Sport as CompetitionSport;

class Against extends GameBase
{
    /**
     * @var AgainstGamePlace[] | Collection
     */
    protected $places;
    /**
     * @var AgainstScore[] | ArrayCollection
     */
    protected $scores;
    protected int $gameRoundNumber;

    public const RESULT_WIN = 1;
    public const RESULT_DRAW = 2;
    public const RESULT_LOST = 3;

    public const HOME = true;
    public const AWAY = false;

    public function __construct(Poule $poule, int $batchNr, DateTimeImmutable $startDateTime, CompetitionSport $competitionSport)
    {
        parent::__construct($poule, $batchNr, $startDateTime, $competitionSport);
        $this->places = new ArrayCollection();
        $this->scores = new ArrayCollection();
    }

//    public function getGameRoundNumber(): int
//    {
//        return $this->gameRoundNumber;
//    }
//
//    public function setGameRoundNumber(int $gameRoundNumber)
//    {
//        $this->gameRoundNumber = $gameRoundNumber;
//    }

    /**
     * @return AgainstScore[] | ArrayCollection
     */
    public function getScores()
    {
        return $this->scores;
    }

    /**
     * @param bool|null $homeaway
     * @return Collection | AgainstGamePlace[]
     */
    public function getPlaces(bool $homeaway = null): Collection
    {
        if ($homeaway === null) {
            return $this->places;
        }
        return $this->places->filter(function ($gamePlace) use ($homeaway): bool {
                return $gamePlace->getHomeaway() === $homeaway;
            });
    }

//    /**
//     * @param \Sports\Place $place
//     * @param bool $homeaway
//     * @return GamePlace
//     */
//    public function addPlace(Place $place, bool $homeaway): GamePlace
//    {
//        return new GamePlace($this, $place, $homeaway);
//    }

    /**
     * @param Place $place
     * @param bool|null $homeaway
     * @return bool
     */
    public function isParticipating(Place $place, bool $homeaway = null): bool
    {
        $places = $this->getPlaces($homeaway)->map(function ($gamePlace) {
            return $gamePlace->getPlace();
        });
        return $places->contains($place);
    }

    public function getHomeAway(Place $place): ?bool
    {
        if ($this->isParticipating($place, self::HOME)) {
            return self::HOME;
        }
        if ($this->isParticipating($place, self::AWAY)) {
            return self::AWAY;
        }
        return null;
    }

    /**
     * @param Map $placeLocationMap
     * @param bool|null $homeAway
     * @return Collection|Competitor[]
     */
    public function getCompetitors( Map $placeLocationMap, bool $homeAway = null ): Collection {
        return $this->getPlaces( $homeAway )->map( function ( AgainstGamePlace $gamePlace ) use ($placeLocationMap) : Competitor {
            return $placeLocationMap->getCompetitor( $gamePlace->getPlace() );
        });
    }

    public function getFinalPhase(): int
    {
        if ($this->getScores()->count()  === 0) {
            return 0;
        }
        return $this->getScores()->last()->getPhase();
    }
}
