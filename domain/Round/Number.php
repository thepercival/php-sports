<?php

declare(strict_types=1);

namespace Sports\Round;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use Sports\Competition;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Order as GameOrder;
use Sports\Game\Together as TogetherGame;
use Sports\Place;
use Sports\Planning\Config as PlanningConfig;
use Sports\Planning\GameAmountConfig as GameAmountConfig;
use Sports\Poule;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\State;
use SportsHelpers\Identifiable;
use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;

class Number extends Identifiable
{
    protected int $number;
    protected RoundNumber|null $next = null;
    /**
     * @var Collection<int|string, Round>
     */
    protected Collection $rounds;
    protected PlanningConfig|null $planningConfig = null;
    /**
     * @var Collection<int|string, GameAmountConfig>
     */
    protected Collection $gameAmountConfigs;

    public function __construct(protected Competition $competition, protected RoundNumber|null $previous = null)
    {
        $this->number = $previous === null ? 1 : $previous->getNumber() + 1;
        $this->rounds = new ArrayCollection();
        $this->gameAmountConfigs = new ArrayCollection();
    }

    public function hasNext(): bool
    {
        return $this->getNext() !== null;
    }

    public function getNext(): RoundNumber|null
    {
        return $this->next;
    }

    public function createNext(): RoundNumber
    {
        $next = new RoundNumber($this->getCompetition(), $this);
        $this->next = $next;
        return $next;
    }

    public function detachFromNext(): void
    {
        $this->next = null;
    }

    public function detachFromPrevious(): void
    {
        if ($this->previous === null) {
            return;
        }
        $this->previous->detachFromNext();
        $this->previous = null;
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
     * @return Collection<int|string, Round>
     */
    public function getRounds(): Collection
    {
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
        return array_values($poules);
    }

    /**
     * @param int $order
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGames(int $order): array
    {
        $games = [];
        foreach ($this->getPoules() as $poule) {
            $games = array_merge($games, $poule->getGames());
        }

        $baseSort = function (TogetherGame|AgainstGame $g1, TogetherGame|AgainstGame $g2): int {
            $field1 = $g1->getField();
            $field2 = $g2->getField();
            if ($field1 === null || $field2 === null) {
                return 0;
            }
            $retVal = $field1->getPriority() - $field2->getPriority();
            return $this->isFirst() ? $retVal : -$retVal;
        };

        if ($order === GameOrder::ByBatch) {
            uasort(
                $games,
                function (TogetherGame|AgainstGame $g1, TogetherGame|AgainstGame $g2) use ($baseSort): int {
                    if ($g1->getBatchNr() === $g2->getBatchNr()) {
                        return $baseSort($g1, $g2);
                    }
                    return $g1->getBatchNr() - $g2->getBatchNr();
                }
            );
        } elseif ($order === GameOrder::ByDate) {
            uasort(
                $games,
                function (TogetherGame|AgainstGame $g1, TogetherGame|AgainstGame $g2) use ($baseSort): int {
                    $start1 = $g1->getStartDateTime()->getTimestamp();
                    $start2 = $g2->getStartDateTime()->getTimestamp();
                    if ($start1 === $start2) {
                        return $baseSort($g1, $g2);
                    }
                    return $start1 - $start2;
                }
            );
        }
        return array_values($games);
    }

    public function allPoulesHaveGames(): bool
    {
        foreach ($this->getRounds() as $round) {
            foreach ($round->getPoules() as $poule) {
                if ($poule->getAgainstGames()->count() + $poule->getTogetherGames()->count() === 0) {
                    return false;
                }
            }
        }
        return true;
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
        return array_values($places);
    }

    public function getNrOfPlaces(): int
    {
        $nrOfPlaces = 0;
        foreach ($this->getPoules() as $poule) {
            $nrOfPlaces += $poule->getPlaces()->count();
        }
        return $nrOfPlaces;
    }

    public function getPlanningConfig(): PlanningConfig|null
    {
        return $this->planningConfig;
    }

    public function setPlanningConfig(PlanningConfig|null $config = null): void
    {
        $this->planningConfig = $config;
    }

    /**
     * @throws Exception
     */
    public function getValidPlanningConfig(): PlanningConfig
    {
        if ($this->planningConfig !== null) {
            return $this->planningConfig;
        }
        $previous = $this->getPrevious();
        if ($previous === null) {
            throw new Exception('de plannings-instellingen kunnen niet gevonden worden', E_ERROR);
        }
        return $previous->getValidPlanningConfig();
    }

    public function hasMultipleSports(): bool
    {
        return $this->getCompetition()->hasMultipleSports();
    }

    /**
     * @return Collection<int|string, CompetitionSport>
     */
    public function getCompetitionSports(): Collection
    {
        return $this->getCompetition()->getSports();
    }

    public function getFirstStartDateTime(): DateTimeImmutable
    {
        $games = $this->getGames(GameOrder::ByDate);
        $firstGame = reset($games);
        if ($firstGame === false) {
            throw new Exception('er zijn geen wedstrijden voor dit rondenummer', E_ERROR);
        }
        return $firstGame->getStartDateTime();
    }

    public function getLastStartDateTime(): DateTimeImmutable
    {
        $games = $this->getGames(GameOrder::ByDate);
        $lastRecentGame = end($games);
        if ($lastRecentGame === false) {
            throw new Exception('er zijn geen wedstrijden voor dit rondenummer', E_ERROR);
        }
        return $lastRecentGame->getStartDateTime();
    }

    /**
     * @return Collection<int|string, GameAmountConfig>
     */
    public function getGameAmountConfigs(): Collection
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

    /**
     * @throws Exception
     */
    public function getValidGameAmountConfig(CompetitionSport $competitionSport): GameAmountConfig
    {
        $gameAmountConfig = $this->getGameAmountConfig($competitionSport);
        if ($gameAmountConfig !== null) {
            return $gameAmountConfig;
        }
        $previous = $this->getPrevious();
        if ($previous === null) {
            throw new Exception('het aantal ingestelde wedstrijden kan niet gevonden worden', E_ERROR);
        }
        return $previous->getValidGameAmountConfig($competitionSport);
    }

    /**
     * @return list<GameAmountConfig>
     */
    public function getValidGameAmountConfigs(): array
    {
        return array_values($this->getCompetitionSports()->map(
            function (CompetitionSport $competitionSport): GameAmountConfig {
                return $this->getValidGameAmountConfig($competitionSport);
            }
        )->toArray());
    }

    /**
     * @return list<AgainstSportVariant|AllInOneGameSportVariant|SingleSportVariant>
     */
    public function createSportVariants(): array
    {
        return array_map(
            fn (
                GameAmountConfig $gameAmountConfig
            ): AgainstSportVariant|AllInOneGameSportVariant|SingleSportVariant => $gameAmountConfig->createVariant(),
            $this->getValidGameAmountConfigs()
        );
    }


    public function createPouleStructure(): PouleStructure
    {
        $placesPerPoule = [];
        foreach ($this->getPoules() as $poule) {
            $placesPerPoule[] = $poule->getPlaces()->count();
        }
        return new PouleStructure(...$placesPerPoule);
    }

    public function detach(): void
    {
        $next = $this->getNext();
        if ($next !== null) {
            $next->detach();
            $this->detachFromNext();
        }
        $this->detachFromPrevious();
    }
}
