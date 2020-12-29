<?php

declare(strict_types=1);

namespace Sports\Round;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Sports\Competition;
use Sports\Game as GameBase;
use Sports\Poule;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Sport\ScoreConfig as SportScoreConfig;
use Sports\Sport\GameAmountConfig as SportGameAmountConfig;
use Sports\Qualify\Config as QualifyConfig;
use Sports\Planning\Config as PlanningConfig;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Sport;
use Sports\State;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Together as TogetherGame;
use SportsHelpers\Identifiable;
use SportsHelpers\SportConfig;

class Number extends Identifiable
{
    /**
    * @var Competition
    */
    protected $competition;
    /**
    * @var int
    */
    protected $number;
    /**
     * @var RoundNumber
     */
    protected $previous;
    /**
     * @var ?RoundNumber
     */
    protected $next;
    /**
     * @var Round[] | ArrayCollection
     */
    protected $rounds;
    /**
     * @var bool
     */
    protected $hasPlanning;
    /**
     * @var QualifyConfig[] | ArrayCollection
     */
    protected $qualifyConfigs;
    /**
     * @var SportGameAmountConfig[] | ArrayCollection
     */
    protected $sportGameAmountConfigs;
    /**
     * @var SportScoreConfig[] | ArrayCollection
     */
    protected $sportScoreConfigs;
    /**
     * @var PlanningConfig
     */
    protected $planningConfig;

    public function __construct(Competition $competition, RoundNumber $previous = null)
    {
        $this->competition = $competition;
        $this->previous = $previous;
        $this->number = $previous === null ? 1 : $previous->getNumber() + 1;
        $this->qualifyConfigs = new ArrayCollection();
        $this->sportScoreConfigs = new ArrayCollection();
        $this->sportGameAmountConfigs = new ArrayCollection();
        $this->hasPlanning = false;
    }

    public function hasNext(): bool
    {
        return $this->getNext() !== null;
    }

    public function getNext(): ?RoundNumber
    {
        return $this->next;
    }

    public function createNext(): RoundNumber
    {
        $this->next = new RoundNumber($this->getCompetition(), $this);
        return $this->getNext();
    }

    public function removeNext()
    {
        $this->next = null;
    }

    /**
     * voor serialization
     *
     * @param RoundNumber $roundNumber
     */
    public function setNext(RoundNumber $roundNumber)
    {
        $this->next = $roundNumber;
    }

    public function hasPrevious(): bool
    {
        return $this->previous !== null;
    }

    public function getPrevious(): ?RoundNumber
    {
        return $this->previous;
    }

    public function getCompetition(): Competition
    {
        return $this->competition;
    }

    public function setCompetition(Competition $competition)
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

    public function getFirst()
    {
        if ($this->getPrevious() !== null) {
            return $this->getPrevious()->getFirst();
        }
        return $this;
    }

    public function isFirst()
    {
        return ($this->getPrevious() === null);
    }

    /**
     * @return ArrayCollection|Round[]
     */
    public function getRounds()
    {
        if ($this->rounds === null) {
            $this->rounds = new ArrayCollection();
        }
        return $this->rounds;
    }

    public function getARound(): Round
    {
        return $this->getRounds()->first();
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
     * @return array | Poule[]
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
     * @return array|AgainstGame[]|TogetherGame[]
     */
    public function getGames(int $order = null): array
    {
        $games = [];
        foreach ($this->getPoules() as $poule) {
            $games = array_merge($games, $poule->getGames()->toArray());
        }

        if ($order === GameBase::ORDER_BY_BATCH) {
            uasort(
                $games,
                /**
                 * @param TogetherGame|AgainstGame $g1
                 * @param TogetherGame|AgainstGame $g2
                 * @return int
                 */
                function ($g1, $g2): int {
                    if ($g1->getBatchNr() === $g2->getBatchNr()) {
                        if( $g1->getField() !== null && $g2->getField() !== null ) {
                            $retVal = $g1->getField()->getPriority() - $g2->getField()->getPriority();
                            return $this->isFirst() ? $retVal : -$retVal;
                        }
                    }
                    return $g1->getBatchNr() - $g2->getBatchNr();
                });
        }
        return $games;
    }

    /**
     * @return array | \Sports\Place[]
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

    public function setPlanningConfig(PlanningConfig $config = null)
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

    public function setHasPlanning(bool $hasPlanning)
    {
        $this->hasPlanning = $hasPlanning;
    }

    public function hasMultipleSports(): bool
    {
        return $this->getCompetition()->hasMultipleSports();
    }

    /**
     * @return ArrayCollection | PersistentCollection | CompetitionSport[]
     */
    public function getCompetitionSports()
    {
        return $this->getCompetition()->getSports();
    }

    /**
     * @return array | SportConfig[]
     */
    public function getSportConfigs(): array
    {
        return $this->getCompetition()->getSports()->map( function (CompetitionSport $competitionSport ): SportConfig {
            $gameAmountConfig = $this->getValidSportGameAmountConfig($competitionSport);
            return $competitionSport->createConfig( $gameAmountConfig->getAmount() );
        })->toArray();
    }

    /**
     * @return ArrayCollection | QualifyConfig[]
     */
    public function getQualifyConfigs()
    {
        return $this->qualifyConfigs;
    }

    public function getCompetitionSport(Sport $sport): ?CompetitionSport
    {
        $filtered = $this->getCompetitionSports()->filter(function (CompetitionSport $competitionSport) use ($sport): bool {
            return $competitionSport->getSport() === $sport;
        });
        return $filtered->count() === 1 ? $filtered->first() : null;
    }

    /**
     * @return ArrayCollection | SportGameAmountConfig[]
     */
    public function getSportGameAmountConfigs()
    {
        return $this->sportGameAmountConfigs;
    }

    public function getSportGameAmountConfig(CompetitionSport $competitionSport): ?SportGameAmountConfig
    {
        $sportGameAmountConfigs = $this->sportGameAmountConfigs->filter(function (SportGameAmountConfig $sportGameAmountConfigIt) use ($competitionSport): bool {
            return $sportGameAmountConfigIt->getCompetitionSport() === $competitionSport;
        });
        if ($sportGameAmountConfigs->count() === 1) {
            return $sportGameAmountConfigs->first();
        }
        return null;
    }

    public function getValidSportGameAmountConfig(CompetitionSport $competitionSport): SportGameAmountConfig
    {
        $sportGameAmountConfig = $this->getSportGameAmountConfig($competitionSport);
        if ($sportGameAmountConfig !== null) {
            return $sportGameAmountConfig;
        }
        return $this->getPrevious()->getValidSportGameAmountConfig($competitionSport);
    }

    /**
     * @return array|SportGameAmountConfig[]
     */
    public function getValidSportGameAmountConfigs(): array
    {
        return $this->getCompetitionSports()->map(
            function (CompetitionSport $competitionSport): SportGameAmountConfig {
                return $this->getValidSportGameAmountConfig($competitionSport);
            }
        )->toArray();
    }

    /**
     * @return ArrayCollection | SportScoreConfig[]
     */
    public function getSportScoreConfigs()
    {
        return $this->sportScoreConfigs;
    }

    public function getSportScoreConfig(CompetitionSport $competitionSport): ?SportScoreConfig
    {
        $sportScoreConfigs = $this->sportScoreConfigs->filter(function (SportScoreConfig $sportScoreConfigIt) use ($competitionSport): bool {
            return $sportScoreConfigIt->isFirst() && $sportScoreConfigIt->getCompetitionSport() === $competitionSport;
        });
        if ($sportScoreConfigs->count() === 1) {
            return $sportScoreConfigs->first();
        }
        return null;
    }

    public function getValidSportScoreConfig(CompetitionSport $competitionSport): SportScoreConfig
    {
        $sportScoreConfig = $this->getSportScoreConfig($competitionSport);
        if ($sportScoreConfig !== null) {
            return $sportScoreConfig;
        }
        return $this->getPrevious()->getValidSportScoreConfig($competitionSport);
    }

    /**
     * @return array|SportScoreConfig[]
     */
    public function getValidSportScoreConfigs(): array
    {
        return $this->getCompetitionSports()->map(
            function (CompetitionSport $competitionSport): SportScoreConfig {
                return $this->getValidSportScoreConfig($competitionSport);
            }
        )->toArray();
    }

    /**
     * @return Collection|SportScoreConfig[]
     */
    public function getFirstSportScoreConfigs(): Collection
    {
        return $this->getSportScoreConfigs()->filter(function (SportScoreConfig $config): bool {
            return $config->isFirst();
        });
    }

    /**
     * @return ArrayCollection | QualifyConfig[]
     */
    public function QualifyConfigs()
    {
        return $this->qualifyConfigs;
    }

    public function getQualifyConfig(CompetitionSport $competitionSport): ?QualifyConfig
    {
        $qualifyConfigs = $this->qualifyConfigs->filter(function (QualifyConfig $qualifyConfigIt) use ($competitionSport): bool {
            return $qualifyConfigIt->getCompetitionSport() === $competitionSport;
        });
        if ($qualifyConfigs->count() === 1) {
            return $qualifyConfigs->first();
        }
        return null;
    }

    public function getValidQualifyConfig(CompetitionSport $competitionSport): QualifyConfig
    {
        $qualifyConfig = $this->getQualifyConfig($competitionSport);
        if ($qualifyConfig !== null) {
            return $qualifyConfig;
        }
        return $this->getPrevious()->getValidQualifyConfig($competitionSport);
    }

    /**
     * @return array|QualifyConfig[]
     */
    public function getValidQualifyConfigs(): array
    {
        return $this->getCompetitionSports()->map(
            function (CompetitionSport $competitionSport): QualifyConfig {
                return $this->getValidQualifyConfig($competitionSport);
            },
        )->toArray();
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
}
