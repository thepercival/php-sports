<?php

declare(strict_types=1);

namespace Sports;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use InvalidArgumentException;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Competitor\StartLocation;
use Sports\Game\Against as AgainstGame;
use Sports\Game\State as GameState;
use Sports\Game\Together as TogetherGame;

use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\AgainstConfig as AgainstQualifyConfig;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\QualifyTarget as QualifyTarget;
use Sports\Round\Number as RoundNumber;
use Sports\Structure\Cell as StructureCell;
use Sports\Score\Config as ScoreConfig;
use Sports\Structure\PathNode as StructurePathNode;
use SportsHelpers\Identifiable;
use SportsHelpers\PlaceLocationInterface;
use SportsHelpers\PouleStructures\BalancedPouleStructure;
use SportsHelpers\Sport\Variant\MinNrOfPlacesCalculator;

/**
 * @api
 */
class Round extends Identifiable
{
    protected string|null $name = null;
    /**
     * @var Collection<int|string, Poule>
     */
    protected Collection $poules;
    /**
     * @var Collection<int|string, QualifyGroup>
     */
    protected Collection $qualifyGroups;
    /**
     * @var Collection<int|string, HorizontalPoule>
     */
    protected Collection $losersHorizontalPoules;
    /**
     * @var Collection<int|string, HorizontalPoule>
     */
    protected Collection $winnersHorizontalPoules;
    /**
     * @psalm-var Collection<int|string, AgainstQualifyConfig>
     */
    protected Collection $againstQualifyConfigs;
    /**
     * @var Collection<int|string, ScoreConfig>
     */
    protected Collection $scoreConfigs;
    protected StructurePathNode $structurePathNode;

    public const int MAX_LENGTH_NAME = 20;

    public const int ORDER_NUMBER_POULE = 1;
    public const int ORDER_POULE_NUMBER = 2;

    public const int QUALIFYORDER_CROSS = 1;
    public const int QUALIFYORDER_RANK = 2;
    public const int QUALIFYORDER_DRAW = 4;
    public const int QUALIFYORDER_CUSTOM1 = 8;
    public const int QUALIFYORDER_CUSTOM2 = 16;

    public const int RANK_NUMBER_POULE = 6;
    public const int RANK_POULE_NUMBER = 7;

    public function __construct(
        protected StructureCell $structureCell,
        protected QualifyGroup|null $parentQualifyGroup = null
    ) {
        if (!$structureCell->getRounds()->contains($this)) {
            $structureCell->getRounds()->add($this);
        }
//        if (!$category->getRounds()->contains($this)) {
//            $category->getRounds()->add($this);
//        }
        $this->structurePathNode = $this->constructStructurePathNode();
        $this->poules = new ArrayCollection();
        $this->qualifyGroups = new ArrayCollection();
        $this->againstQualifyConfigs = new ArrayCollection();
        $this->scoreConfigs = new ArrayCollection();
        $this->winnersHorizontalPoules = new ArrayCollection();
        $this->losersHorizontalPoules = new ArrayCollection();
    }

    public function getStructureCell(): StructureCell
    {
        return $this->structureCell;
    }

    public function getCategory(): Category
    {
        return $this->getStructureCell()->getCategory();
    }

    public function getNumber(): RoundNumber
    {
        return $this->structureCell->getRoundNumber();
    }

    public function getNumberAsValue(): int
    {
        return $this->getNumber()->getNumber();
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
        if ($name !== null && strlen($name) > self::MAX_LENGTH_NAME) {
            throw new InvalidArgumentException(
                'de naam mag maximaal ' . self::MAX_LENGTH_NAME . ' karakters bevatten',
                E_ERROR
            );
        }
        $this->name = $name;
    }

    /**
     * @return Collection<int|string, QualifyGroup>
     */
    public function getQualifyGroups(): Collection
    {
        return $this->qualifyGroups;
    }

    /**
     * @param QualifyTarget $target
     * @return Collection<int|string, QualifyGroup>
     */
    public function getTargetQualifyGroups(QualifyTarget $target): Collection
    {
        return $this->qualifyGroups->filter(function (QualifyGroup $qualifyGroup) use ($target): bool {
            return $qualifyGroup->getTarget() === $target;
        });
    }

    /**
     * @return list<QualifyGroup>
     */
    public function getQualifyGroupsLosersReversed(): array
    {
        $winners = [];
        foreach ($this->getTargetQualifyGroups(QualifyTarget::Winners) as $qualifyGroup) {
            array_push($winners, $qualifyGroup);
        }

        $losers = [];
        foreach ($this->getTargetQualifyGroups(QualifyTarget::Losers) as $qualifyGroup) {
            array_unshift($losers, $qualifyGroup);
        }

        return array_merge($winners, $losers);
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

    /*public function clearRoundAndQualifyGroups(QualifyTarget $target): void
    {
        $nextRoundNumber = $this->getStructureCell()->getRoundNumber()->getNext();
        $rounds = $nextRoundNumber !== null ? $nextRoundNumber->getRounds() : null;
        $qualifyGroupsToRemove = $this->getTargetQualifyGroups($target);
        foreach ($qualifyGroupsToRemove as $qualifyGroupToRemove) {
            $this->qualifyGroups->removeElement($qualifyGroupToRemove);
            if ($rounds !== null && $rounds->contains($qualifyGroupToRemove->getChildRound())) {
                $rounds->removeElement($qualifyGroupToRemove->getChildRound());
            }
        }
    }*/


//    protected function sortQualifyGroups() {
//        uasort( $this->qualifyGroups, function( QualifyGroup $qualifyGroupA, QualifyGroup $qualifyGroupB) {
//            if ($qualifyGroupA->getTarget() < $qualifyGroupB->getTarget()) {
//                return 1;
//            }
//            if ($qualifyGroupA->getTarget() > $qualifyGroupB->getTarget()) {
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

    public function getQualifyGroup(QualifyTarget $target, int $qualifyGroupNumber): ?QualifyGroup
    {
        $qualifyGroup = $this->getTargetQualifyGroups($target)->filter(function (QualifyGroup $qualifyGroup) use ($qualifyGroupNumber): bool {
            return $qualifyGroup->getNumber() === $qualifyGroupNumber;
        })->last();
        return $qualifyGroup === false ? null : $qualifyGroup;
    }

    public function getBorderQualifyGroup(QualifyTarget $target): QualifyGroup|null
    {
        $qualifyGroups = $this->getTargetQualifyGroups($target);
        $last = $qualifyGroups->last();
        return $last !== false ? $last : null;
    }

    public function getNrOfDropoutPlaces(): int
    {
        return $this->getNrOfPlaces() - $this->getNrOfPlacesChildren();
    }

    /**
     * @return list<Round>
     */
    public function getChildren(): array
    {
        return array_values(array_map(function (QualifyGroup $qualifyGroup): Round {
            return $qualifyGroup->getChildRound();
        }, $this->getQualifyGroups()->toArray()));
    }

    public function getChild(QualifyTarget $target, int $qualifyGroupNumber): ?Round
    {
        $qualifyGroup = $this->getQualifyGroup($target, $qualifyGroupNumber);
        return $qualifyGroup !== null ? $qualifyGroup->getChildRound() : null;
    }

    /**
     * @return Collection<int|string, Poule>
     */
    public function getPoules(): Collection
    {
        return $this->poules;
    }

    public function getPoule(int $number): Poule
    {
        foreach ($this->getPoules() as $poule) {
            if ($poule->getNumber() === $number) {
                return $poule;
            }
        }
        throw new \Exception("poule kan niet gevonden worden", E_ERROR);
    }

    public function getFirstPoule(): Poule
    {
        return $this->getPoule(1);
    }

    public function getLastPoule(): Poule
    {
        return $this->getPoule($this->getPoules()->count());
    }

    public function addPlace(): void
    {
        $pouleStructure = $this->createPouleStructure();
        $pouleNr = $pouleStructure->getFirstLesserNrOfPlacesPouleNr();
        new Place($this->getPoule($pouleNr));
    }

    public function removePlace(): int
    {
        $pouleStructure = $this->createPouleStructure();
        $pouleNr = $pouleStructure->getLastGreaterNrOfPlacesPouleNr();
        $poule = $this->getPoule($pouleNr);

        $poulePlaces = $poule->getPlaces();
        $nrOfPoulePlaces = count($poulePlaces);
        $lastPlace = $poulePlaces->last();
        $nrOfRemovedPoulePlaces = 0;
        if ($lastPlace !== false && $poulePlaces->removeElement($lastPlace)) {
            $nrOfRemovedPoulePlaces++;
        };

        $sportVariants = $poule->getCompetition()->createSportVariants();
        $minNrOfPlacesPerPoule = (new MinNrOfPlacesCalculator())->getMinNrOfPlacesPerPoule($sportVariants);

        if (count($poulePlaces) < $minNrOfPlacesPerPoule) {
            $this->removePoule();
            return $nrOfPoulePlaces;
        }
        return $nrOfRemovedPoulePlaces;
    }

    public function addPoule(): Poule
    {
        $lastPoule = $this->getLastPoule();
        $poule = new Poule($this);
        for ($i = 1 ; $i <= $lastPoule->getPlaces()->count() ; $i++) {
            new Place($poule);
        }
        return $this->getLastPoule();
    }

    public function removePoule(): Poule
    {
        $lastPoule = $this->getLastPoule();
        $this->poules->removeElement($lastPoule);
        if ($this->poules->count() === 0) {
            $this->detach();
        }
        return $lastPoule;
    }

    public function getRoot(): Round
    {
        $parent = $this->getParent();
        return $parent !== null ? $parent->getRoot() : $this;
    }

    public function isRoot(): bool
    {
        return $this->getParentQualifyGroup() === null;
    }

    public function getParent(): Round|null
    {
        $parent = $this->getParentQualifyGroup();
        return  $parent!== null ? $parent->getParentRound() : null;
    }

    public function getParentQualifyGroup(): QualifyGroup|null
    {
        return $this->parentQualifyGroup;
    }

    /**
     * @param QualifyTarget $qualifyTarget
     * @return Collection<int|string, HorizontalPoule>
     */
    public function getHorizontalPoules(QualifyTarget $qualifyTarget): Collection
    {
        if ($qualifyTarget === QualifyTarget::Winners) {
            return $this->winnersHorizontalPoules;
        }
        return $this->losersHorizontalPoules;
    }

    public function getHorizontalPoule(QualifyTarget $target, int $number): HorizontalPoule
    {
        $foundHorPoules = $this->getHorizontalPoules($target)->filter(function (HorizontalPoule $horPoule) use ($number): bool {
            return $horPoule->getNumber() === $number;
        });
        $firstHorPoule = $foundHorPoules->first();
        if ($firstHorPoule === false) {
            throw new Exception('horizontalPoule can not be undefined', E_ERROR);
        }
        return $firstHorPoule;
    }

    public function onPostLoad(): void
    {
        $this->winnersHorizontalPoules = new ArrayCollection();
        $this->losersHorizontalPoules = new ArrayCollection();
        $this->structurePathNode = $this->constructStructurePathNode();
    }

    public function getFirstPlace(QualifyTarget $target): Place
    {
        return $this->getHorizontalPoule($target, 1)->getFirstPlace();
    }

    /**
     * @param int|null $order
     * @return array<Place>
     */
    public function getPlaces(int|null $order = null): array
    {
        $places = [];
        if ($order === Round::ORDER_NUMBER_POULE) {
            foreach ($this->getHorizontalPoules(QualifyTarget::Winners) as $horPoule) {
                $places = array_merge($places, $horPoule->getPlaces()->toArray());
            }
        } else {
            foreach ($this->getPoules() as $poule) {
                $places = array_merge($places, $poule->getPlaces()->toArray());
            }
        }
        return $places;
    }

    public function getPlace(PlaceLocationInterface $placeLocation): Place
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
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGames(): array
    {
        $games = [];
        foreach ($this->getPoules() as $poule) {
            $games = array_merge($games, $poule->getGames());
        }
        return array_values($games);
    }

    /**
     * @param GameState $state
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGamesWithState(GameState $state): array
    {
        $games = [];
        foreach ($this->getPoules() as $poule) {
            $games = array_merge($games, $poule->getGamesWithState($state));
        }
        return array_values($games);
    }

    public function getGamesState(): GameState
    {
        if( count($this->getGames()) === 0 ) {
            return GameState::Created;
        }

        $allPlayed = true;
        foreach ($this->getPoules() as $poule) {
            $games = $poule->getGames();
            if( count($games) === 0 && $this->getNumberAsValue() === 1) { // BYE
                continue;
            }
            if ($poule->getGamesState() !== GameState::Finished) {
                $allPlayed = false;
                break;
            }
        }
        if ($allPlayed) {
            return GameState::Finished;
        }
        foreach ($this->getPoules() as $poule) {
            if ($poule->getGamesState() !== GameState::Created) {
                return GameState::InProgress;
            }
        }
        return GameState::Created;
    }

    public function hasBegun(): bool
    {
        return $this->getGamesState()->value > GameState::Created->value;
    }

    public function getNrOfPlaces(): int
    {
        $nrOfPlaces = 0;
        foreach ($this->getPoules() as $poule) {
            $nrOfPlaces += $poule->getPlaces()->count();
        }
        return $nrOfPlaces;
    }

    public function getNrOfPlacesChildren(QualifyTarget|null $target = null): int
    {
        $nrOfPlacesChildRounds = 0;
        if ($target === null) {
            $qualifyGroups = $this->getQualifyGroups();
        } else {
            $qualifyGroups = $this->getTargetQualifyGroups($target);
        }

        foreach ($qualifyGroups as $qualifyGroup) {
            $nrOfPlacesChildRounds += $qualifyGroup->getChildRound()->getNrOfPlaces();
        }
        return $nrOfPlacesChildRounds;
    }

    public function getCompetition(): Competition
    {
        return $this->getStructureCell()->getRoundNumber()->getCompetition();
    }

    public function getCompetitionSport(Sport $sport): ?CompetitionSport
    {
        $filtered = $this->getCategory()->getCompetitionSports()->filter(
            function (CompetitionSport $competitionSport) use ($sport): bool {
                return $competitionSport->getSport() === $sport;
            }
        );
        $first = $filtered->first();
        return $first !== false ? $first : null;
    }

    /**
     * @return Collection<int|string, ScoreConfig>
     */
    public function getScoreConfigs(): Collection
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
            throw new Exception('de score-regels kunnen niet gevonden worden', E_ERROR);
        }
        return $parent->getValidScoreConfig($competitionSport);
    }

    /**
     * @return list<ScoreConfig>
     */
    public function getValidScoreConfigs(): array
    {
        return array_values(
            array_map(
                function (CompetitionSport $competitionSport): ScoreConfig {
                    return $this->getValidScoreConfig($competitionSport);
                }, $this->getCategory()->getCompetitionSports()->toArray()
            )
        );
    }

    /**
     * @return Collection<int|string, ScoreConfig>
     */
    public function getFirstScoreConfigs(): Collection
    {
        return $this->getScoreConfigs()->filter(function (ScoreConfig $config): bool {
            return $config->isFirst();
        });
    }

    /**
     * @return Collection<int|string, AgainstQualifyConfig>
     */
    public function getAgainstQualifyConfigs(): Collection
    {
        return $this->againstQualifyConfigs;
    }

    public function getAgainstQualifyConfig(CompetitionSport $competitionSport): AgainstQualifyConfig|null
    {
        $qualifyConfigs = $this->againstQualifyConfigs->filter(function (AgainstQualifyConfig $qualifyConfigIt) use ($competitionSport): bool {
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
     * @return AgainstQualifyConfig
     * @throws Exception
     */
    public function getValidAgainstQualifyConfig(CompetitionSport $competitionSport): AgainstQualifyConfig
    {
        $qualifyConfig = $this->getAgainstQualifyConfig($competitionSport);
        if ($qualifyConfig !== null) {
            return $qualifyConfig;
        }
        $parent = $this->getParent();
        if ($parent === null) {
            throw new Exception('de punten-regels kunnen niet gevonden worden', E_ERROR);
        }
        return $parent->getValidAgainstQualifyConfig($competitionSport);
    }

    /**
     * @return list<AgainstQualifyConfig>
     */
    public function getValidAgainstQualifyConfigs(): array
    {
        return array_values(
            array_map(
                function (CompetitionSport $competitionSport): AgainstQualifyConfig {
                    return $this->getValidAgainstQualifyConfig($competitionSport);
                }, $this->getStructureCell()->getRoundNumber()->getCompetitionSports()->toArray()
            )
        );
    }

    public function getStructurePathNode(): StructurePathNode
    {
        return $this->structurePathNode;
    }

    protected function constructStructurePathNode(): StructurePathNode
    {
        if ($this->parentQualifyGroup === null) {
            return new StructurePathNode(null, 1, null);
        }
        return new StructurePathNode(
            $this->parentQualifyGroup->getTarget(),
            $this->parentQualifyGroup->getNumber(),
            $this->parentQualifyGroup->getParentRound()->getStructurePathNode()
        );
    }

    /**
     * @throws Exception
     */
    public function createPouleStructure(): BalancedPouleStructure
    {
        $nrOfPlaces = array_map(function (Poule $poule): int {
            return $poule->getPlaces()->count();
        }, $this->getPoules()->toArray() );
        return new BalancedPouleStructure(...$nrOfPlaces);
    }

    public function detach(): void
    {
        $rounds = $this->getStructureCell()->getRounds();
        $rounds->removeElement($this);
        if ($rounds->count() === 0) {
            $this->getStructureCell()->detach();
        }
        $this->parentQualifyGroup = null;
    }

    public function hasQualified(StartLocation $startLocation): bool
    {
        foreach ($this->getPlaces() as $place) {
            if ($place->getStartLocation()?->equals($startLocation)) {
                return true;
            }
        }
        return false;
    }
}
