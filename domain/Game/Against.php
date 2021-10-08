<?php
declare(strict_types=1);

namespace Sports\Game;

use Closure;
use DateTimeImmutable;
use \Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Doctrine\ORM\PersistentCollection;
use SportsHelpers\Against\Side as AgainstSide;
use Sports\Competitor;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game\Event\Card as CardEvent;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Game\Event\Substitution as SubstitutionEvent;
use Sports\Person;
use Sports\Place;
use Sports\Game as GameBase;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Competitor\Map as CompetitorMap;
use Sports\Poule;
use Sports\Qualify\AgainstConfig as QualifyConfig;
use Sports\Score\Against as AgainstScore;
use Sports\Competition\Sport as CompetitionSport;

class Against extends GameBase
{
    /**
     * @phpstan-var ArrayCollection<int|string, AgainstGamePlace>|PersistentCollection<int|string, AgainstGamePlace>
     * @psalm-var ArrayCollection<int|string, AgainstGamePlace>
     */
    protected ArrayCollection|PersistentCollection $places;
    /**
     * @phpstan-var ArrayCollection<int|string, AgainstScore>|PersistentCollection<int|string, AgainstScore>
     * @psalm-var ArrayCollection<int|string, AgainstScore>
     */
    protected ArrayCollection|PersistentCollection $scores;

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
     * @phpstan-return ArrayCollection<int|string, AgainstScore>|PersistentCollection<int|string, AgainstScore>
     * @psalm-return ArrayCollection<int|string, AgainstScore>
     */
    public function getScores(): ArrayCollection|PersistentCollection
    {
        return $this->scores;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, AgainstGamePlace>|PersistentCollection<int|string, AgainstGamePlace>
     * @psalm-return ArrayCollection<int|string, AgainstGamePlace>
     */
    public function getPlaces(): ArrayCollection|PersistentCollection
    {
        return $this->places;
    }

    /**
     * @param int|null $side
     * @return list<AgainstGamePlace>
     */
    public function getSidePlaces(int $side = null): array
    {
        if ($side === AgainstSide::HOME) {
            return $this->getHomePlaces();
        } elseif ($side === AgainstSide::AWAY) {
            return $this->getAwayPlaces();
        }
        return array_values($this->getPlaces()->toArray());
    }

    /**
     * @return list<AgainstGamePlace>
     */
    public function getHomePlaces(): array
    {
        return $this->getSidePlacesHelper(AgainstSide::HOME);
    }

    /**
     * @return list<AgainstGamePlace>
     */
    public function getAwayPlaces(): array
    {
        return $this->getSidePlacesHelper(AgainstSide::AWAY);
    }

    /**
     * @return list<AgainstGamePlace>
     */
    protected function getSidePlacesHelper(int $side): array
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
     * @param int|null $side
     * @return bool
     */
    public function isParticipating(Place $place, int $side = null): bool
    {
        $places = array_map(function (AgainstGamePlace $gamePlace): Place {
            return $gamePlace->getPlace();
        }, $this->getSidePlaces($side));
        return array_search($place, $places, true) !== false;
    }

    public function getSide(Place $place): ?int
    {
        if ($this->isParticipating($place, AgainstSide::HOME)) {
            return AgainstSide::HOME;
        }
        if ($this->isParticipating($place, AgainstSide::AWAY)) {
            return AgainstSide::AWAY;
        }
        return null;
    }

    /**
     * @param CompetitorMap $competitorMap
     * @param int|null $side
     * @return list<Competitor|null>
     */
    public function getCompetitors(CompetitorMap $competitorMap, int $side = null): array
    {
        return array_values(array_map(
            function (AgainstGamePlace $gamePlace) use ($competitorMap): Competitor|null {
                return $competitorMap->getCompetitor($gamePlace->getPlace());
            },
            $this->getSidePlaces($side)
        ));
    }

    public function getFinalPhase(): int
    {
        $lastScore = $this->getScores()->last();
        return $lastScore !== false ? $lastScore->getPhase() : 0;
    }

    /**
     * @return array<GoalEvent|CardEvent|SubstitutionEvent>
     */
    public function getEvents(): array
    {
        $events = [];
        foreach( $this->getPlaces() as $gamePlace ) {
            foreach( $gamePlace->getEvents() as $gamePlaceEvent ) {
                $events[] = $gamePlaceEvent;
            }
        }
        uasort($events, function (
            GoalEvent|CardEvent|SubstitutionEvent $eventA,
            GoalEvent|CardEvent|SubstitutionEvent $eventB
        ): int {
            return $eventA->getMinute() < $eventB->getMinute() ? -1 : 1;
        });
        return $events;
    }

    public function getParticipation(Person $person): Participation|null
    {
        foreach( $this->getPlaces() as $gamePlace ) {
            $participation = $gamePlace->getParticipation($person);
            if( $participation !== null ) {
                return $participation;
            }
        }
        return null;
    }
}
