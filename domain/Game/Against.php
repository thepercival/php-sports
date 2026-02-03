<?php

declare(strict_types=1);

namespace Sports\Game;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Game as GameBase;
use Sports\Game\Event\Card as CardEvent;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Game\Event\Substitution as SubstitutionEvent;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Person;
use Sports\Place;
use Sports\Poule;
use Sports\Qualify\AgainstConfig as QualifyConfig;
use Sports\Score\Against as AgainstScore;
use SportsHelpers\Against\AgainstSide;

final class Against extends GameBase
{
    /**
     * @var Collection<int|string, AgainstGamePlace>
     */
    protected Collection $places;
    /**
     * @var Collection<int|string, AgainstScore>
     */
    protected Collection $scores;
    protected int $homeExtraPoints = 0;
    protected int $awayExtraPoints = 0;

    public function __construct(
        Poule $poule,
        int $batchNr,
        DateTimeImmutable $startDateTime,
        CompetitionSport $competitionSport,
        protected int $gameRoundNumber
    ) {
        parent::__construct($poule, $batchNr, $startDateTime, $competitionSport);
        $this->places = new ArrayCollection();
        $this->scores = new ArrayCollection();
        if (!$poule->getAgainstGames()->contains($this)) {
            $poule->getAgainstGames()->add($this);
        }
    }

    public function getGameRoundNumber(): int
    {
        return $this->gameRoundNumber;
    }

    /**
     * @return Collection<int|string, AgainstScore>
     */
    public function getScores(): Collection
    {
        return $this->scores;
    }

    /**
     * @return Collection<int|string, AgainstGamePlace>
     */
    public function getPlaces(): Collection
    {
        return $this->places;
    }

    /**
     * @param AgainstSide|null $side
     * @return list<AgainstGamePlace>
     */
    public function getSidePlaces(AgainstSide|null $side = null): array
    {
        if ($side === AgainstSide::Home) {
            return $this->getHomePlaces();
        } elseif ($side === AgainstSide::Away) {
            return $this->getAwayPlaces();
        }
        return array_values($this->getPlaces()->toArray());
    }

    /**
     * @param AgainstSide $side
     * @return AgainstGamePlace
     */
    public function getSingleSidePlace(AgainstSide $side): AgainstGamePlace
    {
        $againstGamePlaces = $this->getSidePlaces($side);
        $againstGamePlace = array_shift($againstGamePlaces);
        if ($againstGamePlace === null || count($againstGamePlaces) !== 0) {
            throw new \Exception('againstGameSidePlaces not equal to 1', E_ERROR);
        }
        return $againstGamePlace;
    }

    /**
     * @return list<AgainstGamePlace>
     */
    public function getHomePlaces(): array
    {
        return $this->getSidePlacesHelper(AgainstSide::Home);
    }

    /**
     * @return list<AgainstGamePlace>
     */
    public function getAwayPlaces(): array
    {
        return $this->getSidePlacesHelper(AgainstSide::Away);
    }

    /**
     * @return list<AgainstGamePlace>
     */
    protected function getSidePlacesHelper(AgainstSide $side): array
    {
        return array_values($this->getPlaces()->filter(function (AgainstGamePlace $place) use ($side): bool {
            return $place->getSide() === $side;
        })->toArray());
    }

    public function getAgainstQualifyConfig(): QualifyConfig
    {
        return $this->getRound()->getValidAgainstQualifyConfig($this->getCompetitionSport());
    }

    /**
     * @param Place $place
     * @param AgainstSide|null $side
     * @return bool
     */
    public function isParticipating(Place $place, AgainstSide|null $side = null): bool
    {
        $places = array_map(function (AgainstGamePlace $gamePlace): Place {
            return $gamePlace->getPlace();
        }, $this->getSidePlaces($side));
        return array_search($place, $places, true) !== false;
    }

    public function getSide(Place $place): AgainstSide|null
    {
        if ($this->isParticipating($place, AgainstSide::Home)) {
            return AgainstSide::Home;
        }
        if ($this->isParticipating($place, AgainstSide::Away)) {
            return AgainstSide::Away;
        }
        return null;
    }

//    /**
//     * @param CompetitorMap $competitorMap
//     * @param AgainstSide|null $side
//     * @return list<Competitor|null>
//     */
    /*public function getCompetitors(CompetitorMap $competitorMap, AgainstSide $side = null): array
    {
        return array_map(
            function (AgainstGamePlace $gamePlace) use ($competitorMap): Competitor|null {
                return $competitorMap->getCompetitor($gamePlace->getPlace());
            },
            $this->getSidePlaces($side)
        );
    }*/

    public function getFinalPhase(): int
    {
        $lastScore = $this->getScores()->last();
        return $lastScore !== false ? $lastScore->getPhase() : 0;
    }

    /**
     * @return list<GoalEvent|CardEvent|SubstitutionEvent>
     */
    public function getEvents(): array
    {
        $events = [];
        foreach ($this->getPlaces() as $gamePlace) {
            foreach ($gamePlace->getEvents() as $gamePlaceEvent) {
                $events[] = $gamePlaceEvent;
            }
        }
        uasort($events, function (
            GoalEvent|CardEvent|SubstitutionEvent $eventA,
            GoalEvent|CardEvent|SubstitutionEvent $eventB
        ): int {
            return $eventA->getMinute() < $eventB->getMinute() ? -1 : 1;
        });
        return array_values($events);
    }

    public function getParticipation(Person $person): Participation|null
    {
        foreach ($this->getPlaces() as $gamePlace) {
            $participation = $gamePlace->getParticipation($person);
            if ($participation !== null) {
                return $participation;
            }
        }
        return null;
    }

    public function getHomeExtraPoints(): int
    {
        return $this->homeExtraPoints;
    }

    public function setHomeExtraPoints(int $homeExtraPoints): void
    {
        $this->homeExtraPoints = $homeExtraPoints;
    }

    public function getAwayExtraPoints(): int
    {
        return $this->awayExtraPoints;
    }

    public function setAwayExtraPoints(int $awayExtraPoints): void
    {
        $this->awayExtraPoints = $awayExtraPoints;
    }
}
