<?php


namespace Sports;

use \Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Qualify\AgainstConfig as QualifyAgainstConfig;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Score\Config as ScoreConfig;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Together as TogetherGame;
use Sports\Place\Location as PlaceLocation;
use SportsHelpers\Identifiable;

class Round extends Identifiable
{
    protected string|null $name = null;
    protected QualifyGroup|null $parentQualifyGroup;
    /**
     * @var ArrayCollection<int|string, Poule>
     */
    protected $poules;
    /**
     * @var ArrayCollection<int|string,QualifyGroup>
     */
    protected $qualifyGroups;
    /**
     * @var list<HorizontalPoule>
     */
    protected $losersHorizontalPoules = array();
    /**
     * @var list<HorizontalPoule>
     */
    protected $winnersHorizontalPoules = array();
    /**
     * @var ArrayCollection<int|string,QualifyAgainstConfig>
     */
    protected ArrayCollection $qualifyAgainstConfigs;
    /**
     * @var ArrayCollection<int|string,ScoreConfig>
     */
    protected $scoreConfigs;
    /**
     * @var int
     */
    protected $structureNumber;

    const WINNERS = 1;
    const DROPOUTS = 2;
    const LOSERS = 3;

    const MAX_LENGTH_NAME = 20;

    const ORDER_NUMBER_POULE = 1;
    const ORDER_POULE_NUMBER = 2;

    const QUALIFYORDER_CROSS = 1;
    const QUALIFYORDER_RANK = 2;
    const QUALIFYORDER_DRAW = 4;
    const QUALIFYORDER_CUSTOM1 = 8;
    const QUALIFYORDER_CUSTOM2 = 16;

    const RANK_NUMBER_POULE = 6;
    const RANK_POULE_NUMBER = 7;

    public function __construct(protected Round\Number $number, QualifyGroup|null $parentQualifyGroup = null)
    {
//        $this->winnersHorizontalPoules = array();
//        $this->losersHorizontalPoules = array();
        if (!$number->getRounds()->contains($this)) {
            $number->getRounds()->add($this) ;
        }

        $this->poules = new ArrayCollection();
        $this->setParentQualifyGroup($parentQualifyGroup);
        $this->qualifyGroups = new ArrayCollection();
        $this->qualifyAgainstConfigs = new ArrayCollection();
        $this->scoreConfigs = new ArrayCollection();
    }

    public function getNumber(): Round\Number
    {
        return $this->number;
    }

    public function getNumberAsValue(): int
    {
        return $this->number->getNumber();
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function setName(string|null $name): void
    {
        if ($name !== null && strlen($name) === 0) {
            $name = null;
        }

        if ($name !== null && strlen($name) > static::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException("de naam mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
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
     * @param int|null $winnersOrLosers
     * @return ArrayCollection<int|string,QualifyGroup>
     */
    public function getQualifyGroups(int|null $winnersOrLosers = null): ArrayCollection
    {
        if ($winnersOrLosers === null) {
            return clone $this->qualifyGroups;
        }
        return $this->qualifyGroups->filter(function (QualifyGroup $qualifyGroup) use ($winnersOrLosers): bool {
            return $qualifyGroup->getWinnersOrLosers() === $winnersOrLosers;
        });
    }

    public function addQualifyGroup(QualifyGroup $qualifyGroup): void
    {
        $this->qualifyGroups->add($qualifyGroup);
        // @TODO should automatically sort
        // $this->sortQualifyGroups();
    }

    public function removeQualifyGroup(QualifyGroup $qualifyGroup): bool
    {
        return $this->qualifyGroups->removeElement($qualifyGroup);
    }

    public function clearQualifyGroups(int $winnersOrLosers): void
    {
        $qualifyGroupsToRemove = $this->getQualifyGroups($winnersOrLosers);
        foreach ($qualifyGroupsToRemove as $qualifyGroupToRemove) {
            $this->qualifyGroups->removeElement($qualifyGroupToRemove);
        }
    }


//    protected function sortQualifyGroups() {
//        uasort( $this->qualifyGroups, function( QualifyGroup $qualifyGroupA, QualifyGroup $qualifyGroupB) {
//            if ($qualifyGroupA->getWinnersOrLosers() < $qualifyGroupB->getWinnersOrLosers()) {
//                return 1;
//            }
//            if ($qualifyGroupA->getWinnersOrLosers() > $qualifyGroupB->getWinnersOrLosers()) {
//                return -1;
//            }
//            if ($qualifyGroupA->getNumber() < $qualifyGroupB->getNumber()) {
//                return 1;
//            }
//            if ($qualifyGroupA->getNumber() > $qualifyGroupB->getNumber()) {
//                return -1;
//            }
//            return 0;
//        });
//    }

    public function getQualifyGroup(int $winnersOrLosers, int $qualifyGroupNumber): ?QualifyGroup
    {
        $qualifyGroup = $this->getQualifyGroups($winnersOrLosers)->filter(function ($qualifyGroup) use ($qualifyGroupNumber): bool {
            return $qualifyGroup->getNumber() === $qualifyGroupNumber;
        })->last();
        return $qualifyGroup === false ? null : $qualifyGroup;
    }

    public function getBorderQualifyGroup(int $winnersOrLosers): ?QualifyGroup
    {
        $qualifyGroups = $this->getQualifyGroups($winnersOrLosers);
        $last = $qualifyGroups->last();
        return $last ? $last : null;
    }

    public function getNrOfDropoutPlaces(): int
    {
        // if (this.nrOfDropoutPlaces === null) {
        // @TODO performance check
        return $this->getNrOfPlaces() - $this->getNrOfPlacesChildren();
        // }
        // return this.nrOfDropoutPlaces;
    }

    /**
     * @return array<int|string,Round>
     */
    public function getChildren(): array
    {
        return array_map(function (QualifyGroup $qualifyGroup): Round {
            return $qualifyGroup->getChildRound();
        }, $this->getQualifyGroups()->toArray());
    }

    public function getChild(int $winnersOrLosers, int $qualifyGroupNumber): ?Round
    {
        $qualifyGroup = $this->getQualifyGroup($winnersOrLosers, $qualifyGroupNumber);
        return $qualifyGroup !== null ? $qualifyGroup->getChildRound() : null;
    }

    /**
     * @return ArrayCollection<int|string, Poule>
     */
    public function getPoules(): ArrayCollection
    {
        return $this->poules;
    }

    /**
     * @param ArrayCollection<int|string,Poule> $poules
     *
     * @return void
     */
    public function setPoules(ArrayCollection $poules): void
    {
        $this->poules = $poules;
    }

    public function getPoule(int $number): Poule
    {
        foreach ($this->getPoules() as $poule) {
            if ($poule->getNumber() === $number) {
                return $poule;
            }
        }
        throw new \Exception("poule kan niet gevonden worden");
    }

    public function isRoot(): bool
    {
        return $this->getParentQualifyGroup() === null;
    }

    public function getParent(): Round|null
    {
        $parent = $this->getParentQualifyGroup();
        return  $parent!== null ? $parent->getRound() : null;
    }

    public function getParentQualifyGroup(): ?QualifyGroup
    {
        return $this->parentQualifyGroup;
    }

    public function setParentQualifyGroup(QualifyGroup|null $parentQualifyGroup = null): void
    {
        if ($parentQualifyGroup !== null) {
            $parentQualifyGroup->setChildRound($this);
        }
        $this->parentQualifyGroup = $parentQualifyGroup;
    }

    public function &getHorizontalPoules(int $winnersOrLosers): array
    {
        if ($winnersOrLosers === QualifyGroup::WINNERS) {
            return $this->winnersHorizontalPoules;
        }
        return $this->losersHorizontalPoules;
    }

    public function getHorizontalPoule(int $winnersOrLosers, int $number): HorizontalPoule|null
    {
        $foundHorPoules = array_filter($this->getHorizontalPoules($winnersOrLosers), function ($horPoule) use ($number): bool {
            return $horPoule->getNumber() === $number;
        });
        $first = reset($foundHorPoules);
        return $first ? $first : null;
    }

    public function getFirstPlace(int $winnersOrLosers): Place
    {
        $horPoule = $this->getHorizontalPoule($winnersOrLosers, 1);
        if ($horPoule === null) {
            throw new Exception('de eerste plaats binnen een poule kan niet gevonden worden', E_ERROR);
        }
        return $horPoule->getFirstPlace();
    }

    /**
     * @param int|null $order
     * @return array<Place>
     */
    public function getPlaces(int $order = null): array
    {
        $places = [];
        if ($order === Round::ORDER_NUMBER_POULE) {
            foreach ($this->getHorizontalPoules(QualifyGroup::WINNERS) as $horPoule) {
                $places = array_merge($places, $horPoule->getPlaces());
            }
        } else {
            foreach ($this->getPoules() as $poule) {
                $places = array_merge($places, $poule->getPlaces()->toArray());
            }
        }
        return $places;
    }

    public function getPlace(PlaceLocation $placeLocation): ?Place
    {
        return $this->getPoule($placeLocation->getPouleNr())->getPlace($placeLocation->getPlaceNr());
    }

    public function needsRanking(): bool
    {
        foreach ($this->getPoules() as $pouleIt) {
            if ($pouleIt->needsRanking()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array | AgainstGame[] | TogetherGame[]
     */
    public function getGames(): array
    {
        $games = [];
        foreach ($this->getPoules() as $poule) {
            $games = array_merge($games, $poule->getGames());
        }
        return $games;
    }

    /**
     * @param int $state
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGamesWithState(int $state): array
    {
        $games = [];
        foreach ($this->getPoules() as $poule) {
            $games = array_merge($games, $poule->getGamesWithState($state));
        }
        return array_values($games);
    }

    public function getState(): int
    {
        $allPlayed = true;
        foreach ($this->getPoules() as $poule) {
            if ($poule->getState() !== State::Finished) {
                $allPlayed = false;
                break;
            }
        }
        if ($allPlayed) {
            return State::Finished;
        }
        foreach ($this->getPoules() as $poule) {
            if ($poule->getState() !== State::Created) {
                return State::InProgress;
            }
        }
        return State::Created;
    }

    public function hasBegun(): bool
    {
        return $this->getState() > State::Created;
    }

    public static function getOpposing(int $winnersOrLosers): int
    {
        return $winnersOrLosers === Round::WINNERS ? Round::LOSERS : Round::WINNERS;
    }

    public function getNrOfPlaces(): int
    {
        $nrOfPlaces = 0;
        foreach ($this->getPoules() as $poule) {
            $nrOfPlaces += $poule->getPlaces()->count();
        }
        return $nrOfPlaces;
    }

    public function getNrOfPlacesChildren(int $winnersOrLosers = null): int
    {
        $nrOfPlacesChildRounds = 0;
        $qualifyGroups = $this->getQualifyGroups($winnersOrLosers);
        foreach ($qualifyGroups as $qualifyGroup) {
            $nrOfPlacesChildRounds += $qualifyGroup->getChildRound()->getNrOfPlaces();
        }
        return $nrOfPlacesChildRounds;
    }

    public function getCompetition(): Competition
    {
        return $this->number->getCompetition();
    }

    public function getCompetitionSport(Sport $sport): ?CompetitionSport
    {
        $filtered = $this->number->getCompetitionSports()->filter(function (CompetitionSport $competitionSport) use ($sport): bool {
            return $competitionSport->getSport() === $sport;
        });
        return $filtered->count() === 1 ? $filtered->first() : null;
    }

    /**
     * @return ArrayCollection<int|string, ScoreConfig>
     */
    public function getScoreConfigs(): ArrayCollection
    {
        return $this->scoreConfigs;
    }

    /**
     * @param CompetitionSport $competitionSport
     * @return ScoreConfig|null
     */
    public function getScoreConfig(CompetitionSport $competitionSport): ScoreConfig|null
    {
        $scoreConfigs = $this->scoreConfigs->filter(function (ScoreConfig $scoreConfigIt) use ($competitionSport): bool {
            return $scoreConfigIt->isFirst() && $scoreConfigIt->getCompetitionSport() === $competitionSport;
        });
        $scoreConfig = $scoreConfigs->first();
        return $scoreConfig !== false ? $scoreConfig : null;
    }

    /**
     * @param CompetitionSport $competitionSport
     * @return ScoreConfig
     * @throws Exception
     */
    public function getValidScoreConfig(CompetitionSport $competitionSport): ScoreConfig
    {
        $scoreConfig = $this->getScoreConfig($competitionSport);
        if ($scoreConfig !== null) {
            return $scoreConfig;
        }
        $parent = $this->getParent();
        if ($parent === null) {
            throw new Exception('de score-instellingen kunnen niet gevonden worden', E_ERROR);
        }
        return $parent->getValidScoreConfig($competitionSport);
    }

    /**
     * @return list<ScoreConfig>
     */
    public function getValidScoreConfigs(): array
    {
        return array_values($this->number->getCompetitionSports()->map(
            function (CompetitionSport $competitionSport): ScoreConfig {
                return $this->getValidScoreConfig($competitionSport);
            }
        )->toArray());
    }

    /**
     * @return ArrayCollection<int|string, ScoreConfig>
     */
    public function getFirstScoreConfigs(): ArrayCollection
    {
        return $this->getScoreConfigs()->filter(function (ScoreConfig $config): bool {
            return $config->isFirst();
        });
    }

    /**
     * @return ArrayCollection<int|string,QualifyAgainstConfig>
     */
    public function getQualifyAgainstConfigs(): ArrayCollection
    {
        return $this->qualifyAgainstConfigs;
    }

    public function getQualifyAgainstConfig(CompetitionSport $competitionSport): QualifyAgainstConfig|null
    {
        $qualifyConfigs = $this->qualifyAgainstConfigs->filter(function (QualifyAgainstConfig $qualifyConfigIt) use ($competitionSport): bool {
            return $qualifyConfigIt->getCompetitionSport() === $competitionSport;
        });
        $qualifyConfig = $qualifyConfigs->first();
        if ($qualifyConfig === false) {
            return null;
        }
        return $qualifyConfig;
    }

    /**
     * @param CompetitionSport $competitionSport
     * @return QualifyAgainstConfig
     * @throws Exception
     */
    public function getValidQualifyAgainstConfig(CompetitionSport $competitionSport): QualifyAgainstConfig
    {
        $qualifyConfig = $this->getQualifyAgainstConfig($competitionSport);
        if ($qualifyConfig !== null) {
            return $qualifyConfig;
        }
        $parent = $this->getParent();
        if ($parent === null) {
            throw new Exception('de score-instellingen kunnen niet gevonden worden', E_ERROR);
        }
        return $parent->getValidQualifyAgainstConfig($competitionSport);
    }

    /**
     * @return list<QualifyAgainstConfig>
     */
    public function getValidQualifyAgainstConfigs(): array
    {
        return array_values($this->number->getCompetitionSports()->map(
            function (CompetitionSport $competitionSport): QualifyAgainstConfig {
                return $this->getValidQualifyAgainstConfig($competitionSport);
            },
        )->toArray());
    }
}
