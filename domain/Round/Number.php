<?php

declare(strict_types=1);

namespace Sports\Round;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Sports\Competition;
use Sports\Game as GameBase;
use Sports\Poule;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Planning\GameAmountConfig as GameAmountConfig;
use Sports\Planning\Config as PlanningConfig;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\State;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Together as TogetherGame;
use SportsHelpers\Identifiable;
use SportsHelpers\SportConfig;

class Number extends Identifiable
{
    protected int $number;
    protected RoundNumber|null $next = null;
    /**
     * @var ArrayCollection<int|string, Round>
     */
    protected ArrayCollection $rounds;
    protected bool $hasPlanning;
    /**
     * @var
     */
    protected PlanningConfig|null $planningConfig = null;
    /**
     * @var ArrayCollection<int|string, GameAmountConfig>
     */
    protected $gameAmountConfigs;

    public function __construct(protected Competition $competition, protected RoundNumber|null $previous = null)
    {
        $this->number = $previous === null ? 1 : $previous->getNumber() + 1;
        $this->hasPlanning = false;
        $this->gameAmountConfigs = new ArrayCollection();
    }

    public function hasNext(): bool
    {
        return $this->getNext() !== null;
    }

    public function getNext(): ?RoundNumber
    {
        return $this->next;
    }

    public function createNext(): ?self
    {
        $this->next = new RoundNumber($this->getCompetition(), $this);
        return $this->getNext();
    }

    public function removeNext(): void
    {
        $this->next = null;
    }

    /**
     * voor serialization
     *
     * @param RoundNumber $roundNumber
     * @return void
     */
    public function setNext(RoundNumber $roundNumber): void
    {
        $this->next = $roundNumber;
    }

    public function hasPrevious(): bool
    {
        return $this->previous !== null;
    }

    public function getPrevious(): RoundNumber|null
    {
        return $this->previous;
    }

    public function getCompetition(): Competition
    {
        return $this->competition;
    }

    public function setCompetition(Competition $competition): void
    {
        $this->competition = $competition;
    }

    public function getNumber(): int
    {
        return $this->number;
//        if( $this->getPrevious() === null ) {
//            return 1;
//        }
//        return $this->getPrevious()->getNumber() + 1;
    }

    public function getFirst(): RoundNumber
    {
        $previous = $this->getPrevious();
        if ($previous !== null) {
            return $previous->getFirst();
        }
        return $this;
    }

    public function isFirst(): bool
    {
        return ($this->getPrevious() === null);
    }

    /**
     * @return ArrayCollection<int|string, Round>
     */
    public function getRounds()
    {
        if ($this->rounds === null) {
            $this->rounds = new ArrayCollection();
        }
        return $this->rounds;
    }

    public function needsRanking(): bool
    {
        foreach ($this->getRounds() as $round) {
            if ($round->needsRanking()) {
                return true;
            }
        }
        return false;
    }

    public function getState(): int
    {
        $allRoundsFinished = true;
        foreach ($this->getRounds() as $round) {
            if ($round->getState() === State::Finished) {
                continue;
            }
            $allRoundsFinished = false;
            break;
        }
        if ($allRoundsFinished) {
            return State::Finished;
        }
        $someRoundsNotCreated = false;
        foreach ($this->getRounds() as $round) {
            if ($round->getState() === State::Created) {
                continue;
            }
            $someRoundsNotCreated = true;
            break;
        }
        if ($someRoundsNotCreated) {
            return State::InProgress;
        }
        return State::Created;
    }

    public function hasBegun(): bool
    {
        foreach ($this->getRounds() as $round) {
            if ($round->hasBegun()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return list<Poule>
     */
    public function getPoules(): array
    {
        $poules = [];
        foreach ($this->getRounds() as $round) {
            $poules = array_merge($poules, $round->getPoules()->toArray());
        }
        return $poules;
    }

    /**
     * @param int|null $order
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGames(int $order = null): array
    {
        $games = [];
        foreach ($this->getPoules() as $poule) {
            $games = array_merge($games, $poule->getGames());
        }

        if ($order === GameBase::ORDER_BY_BATCH) {
            uasort(
                $games,
                function (TogetherGame|AgainstGame $g1, TogetherGame|AgainstGame $g2): int {
                    if ($g1->getBatchNr() === $g2->getBatchNr()) {
                        $field1 = $g1->getField();
                        $field2 = $g2->getField();
                        if ($field1 !== null && $field2 !== null) {
                            $retVal = $field1->getPriority() - $field2->getPriority();
                            return $this->isFirst() ? $retVal : -$retVal;
                        }
                    }
                    return $g1->getBatchNr() - $g2->getBatchNr();
                }
            );
        }
        return array_values($games);
    }

    /**
     * @return list<Place>
     */
    public function getPlaces(): array
    {
        $places = [];
        foreach ($this->getPoules() as $poule) {
            $places = array_merge($places, $poule->getPlaces()->toArray());
        }
        return $places;
    }

    public function getNrOfPlaces(): int
    {
        $nrOfPlaces = 0;
        foreach ($this->getPoules() as $poule) {
            $nrOfPlaces += $poule->getPlaces()->count();
        }
        return $nrOfPlaces;
    }

    public function getPlanningConfig(): ?PlanningConfig
    {
        return $this->planningConfig;
    }

    public function setPlanningConfig(PlanningConfig|null $config = null): void
    {
        $this->planningConfig = $config;
    }

    public function getValidPlanningConfig(): PlanningConfig
    {
        if ($this->planningConfig !== null) {
            return $this->planningConfig;
        }
        return $this->getPrevious()->getValidPlanningConfig();
    }

    public function getHasPlanning(): bool
    {
        return $this->hasPlanning;
    }

    public function setHasPlanning(bool $hasPlanning): void
    {
        $this->hasPlanning = $hasPlanning;
    }

    public function hasMultipleSports(): bool
    {
        return $this->getCompetition()->hasMultipleSports();
    }

    /**
     * @return ArrayCollection<int|string, CompetitionSport>
     */
    public function getCompetitionSports(): ArrayCollection
    {
        return $this->getCompetition()->getSports();
    }

    public function getFirstStartDateTime(): DateTimeImmutable
    {
        $games = $this->getGames(GameBase::ORDER_BY_BATCH);
        $leastRecentGame = reset($games);
        return $leastRecentGame->getStartDateTime();
    }

    public function getLastStartDateTime(): DateTimeImmutable
    {
        $games = $this->getGames(GameBase::ORDER_BY_BATCH);
        $mostRecentGame = end($games);
        return $mostRecentGame->getStartDateTime();
    }

    /**
     * @return ArrayCollection<int|string, GameAmountConfig>
     */
    public function getGameAmountConfigs(): ArrayCollection
    {
        return $this->gameAmountConfigs;
    }

    public function getGameAmountConfig(CompetitionSport $competitionSport): GameAmountConfig|null
    {
        $gameAmountConfigs = $this->gameAmountConfigs->filter(function (GameAmountConfig $gameAmountConfigIt) use ($competitionSport): bool {
            return $gameAmountConfigIt->getCompetitionSport() === $competitionSport;
        });
        $gameAmountConfig = $gameAmountConfigs->first();
        return $gameAmountConfig !== false ? $gameAmountConfig : null;
    }

    public function getValidGameAmountConfig(CompetitionSport $competitionSport): GameAmountConfig
    {
        $gameAmountConfig = $this->getGameAmountConfig($competitionSport);
        if ($gameAmountConfig !== null) {
            return $gameAmountConfig;
        }
        $previous = $this->getPrevious();
        if ($previous === null) {
            throw new \Exception('het aantal ingestelde wedstrijden kan niet gevonden worden', E_ERROR);
        }
        return $previous->getValidGameAmountConfig($competitionSport);
    }

    /**
     * @return list<GameAmountConfig>
     */
    public function getValidGameAmountConfigs(): array
    {
        return array_values( $this->getCompetitionSports()->map(
            function (CompetitionSport $competitionSport): GameAmountConfig {
                return $this->getValidGameAmountConfig($competitionSport);
            }
        )->toArray());
    }

    /**
     * @return list<SportConfig>
     */
    public function createSportConfigs(): array
    {
        return array_values( $this->getCompetition()->getSports()->map(function (CompetitionSport $competitionSport): SportConfig {
            $gameAmountConfig = $this->getValidGameAmountConfig($competitionSport);
            return $competitionSport->createConfig($gameAmountConfig->getAmount());
        })->toArray());
    }
}
