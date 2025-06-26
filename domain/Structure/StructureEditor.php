<?php

namespace Sports\Structure;

use Exception;
use Sports\Category;
use Sports\Competition;
use Sports\Competition\Sport\Editor as CompetitionSportEditor;
use Sports\Place;
use Sports\Planning\PlanningConfigService as PlanningConfigService;
use Sports\Poule;
use Sports\Poule\Horizontal\Creator as HorizontalPouleCreator;
use Sports\Qualify\QualifyDistribution as QualifyDistribution;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Rule\Creator as QualifyRuleCreator;
use Sports\Qualify\Rule\Horizontal\Single as HorizontalSingleQualifyRule;
use Sports\Qualify\Rule\Vertical\Single as VerticalSingleQualifyRule;
use Sports\Qualify\QualifyTarget as QualifyTarget;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Structure;
use Sports\Structure as StructureBase;
use SportsHelpers\PlaceRanges;
use SportsHelpers\PouleStructures\BalancedCreator as BalancedPouleStructureCreator;
use SportsHelpers\PouleStructures\BalancedPouleStructure;
use SportsHelpers\Sports\Calculators\MinNrOfPlacesCalculator;

final class StructureEditor
{
    private HorizontalPouleCreator $horPouleCreator;
    private QualifyRuleCreator $rulesCreator;
    private RemovalValidator $removalValidator;
    private PlaceRanges|null $placeRanges = null;

    public function __construct(
        private CompetitionSportEditor $competitionSportEditor,
        private PlanningConfigService $planningConfigService
    ) {
        $this->horPouleCreator = new HorizontalPouleCreator();
        $this->rulesCreator = new QualifyRuleCreator();
        $this->removalValidator = new RemovalValidator();
    }

    public function setPlaceRanges(PlaceRanges $placeRanges): void
    {
        $this->placeRanges = $placeRanges;
    }

    /**
     * @param Competition $competition
     * @param list<int> $pouleStructure
     * @param string|null $categoryName
     * @return StructureBase
     * @throws Exception
     */
    public function create(Competition $competition, array $pouleStructure, string|null $categoryName = null): Structure
    {
        if (count($competition->getCategories()) > 0) {
            throw new \Exception('can not create structure, competition already has categories', E_ERROR);
        }

        $balancedPouleStructure = new BalancedPouleStructure($pouleStructure);
        // begin editing
        $firstRoundNumber = new RoundNumber($competition);
        $this->planningConfigService->createDefault($firstRoundNumber);

        $catergoryName = $categoryName ?? Category::DEFAULTNAME;
        $category = $this->addCategoryHelper(
            $catergoryName, substr($catergoryName, 0, 1),
            $firstRoundNumber,
            $balancedPouleStructure
        );

        $structure = new Structure([$category], $firstRoundNumber);
        foreach ($competition->getSports() as $competitionSport) {
            $this->competitionSportEditor->addToStructure($competitionSport, $structure);
        }

        $rootRound = $category->getRootRound();
        // end editing
        $this->horPouleCreator->create($rootRound);
        $this->rulesCreator->create($rootRound, null);

        return new Structure([$category], $firstRoundNumber);
    }

    /**
     * @param string $name
     * @param Competition $competition
     * @param BalancedPouleStructure $pouleStructure
     * @return Category
     * @throws Exception
     */
    public function addCategory(
        string $name,
        string|null $abbreviation,
        RoundNumber $firstRoundNumber,
        BalancedPouleStructure $pouleStructure
    ): Category {
        $category = $this->addCategoryHelper($name, $abbreviation, $firstRoundNumber, $pouleStructure);
        foreach ($firstRoundNumber->getCompetitionSports() as $competitionSport) {
            $this->competitionSportEditor->addToCategory($competitionSport, $category);
        }
        // end editing

        $rootRound = $category->getRootRound();
        $this->horPouleCreator->create($rootRound);
        $this->rulesCreator->create($rootRound, null);
        return $category;
    }

    protected function addCategoryHelper(
        string $name,
        string|null $abbreviation,
        RoundNumber $firstRoundNumber,
        BalancedPouleStructure $pouleStructure
    ): Category {
        $competition = $firstRoundNumber->getCompetition();
        $category = new Category($competition, $name);
        $category->setAbbreviation($abbreviation);
        $structureCell = new StructureCell($category, $firstRoundNumber);
        $rootRound = new Round($structureCell, null);
        $this->fillRound($rootRound, $pouleStructure);
        return $category;
    }

    /**
     * @param Round $parentRound
     * @param QualifyTarget $qualifyTarget
     * @param list<int> $pouleStructure
     * @param QualifyDistribution $distribution
     * @return Round
     * @throws Exception
     */
    public function addChildRound(
        Round $parentRound,
        QualifyTarget $qualifyTarget,
        array $pouleStructure,
        QualifyDistribution $distribution = QualifyDistribution::HorizontalSnake): Round
    {
        $balancedPouleStructure = new BalancedPouleStructure($pouleStructure);
        $this->placeRanges?->validateStructure($balancedPouleStructure);
        $this->rulesCreator->remove($parentRound);
        // begin editing

        $qualifyGroup = $this->addChildRoundHelper(
            $parentRound,
            $qualifyTarget,
            $balancedPouleStructure,
            $distribution
        );
        // end editing
        $this->horPouleCreator->create($qualifyGroup->getChildRound());
        $this->rulesCreator->create($parentRound, null);
        return $qualifyGroup->getChildRound();
    }

    private function addChildRoundHelper(
        Round $parentRound,
        QualifyTarget $qualifyTarget,
        BalancedPouleStructure $pouleStructure,
        QualifyDistribution $distribution
    ): QualifyGroup
    {
        $nextStructureCell = $parentRound->getStructureCell()->getNext();
        if ($nextStructureCell === null) {
            $nextStructureCell = $parentRound->getStructureCell()->createNext();
        }
        $qualifyGroup = new QualifyGroup($parentRound, $qualifyTarget, $nextStructureCell);
        $qualifyGroup->setDistribution($distribution);
        $this->fillRound($qualifyGroup->getChildRound(), $pouleStructure);
        return $qualifyGroup;
    }

    /**
     * voor een ronde kun je:
     * A een plek toevoegen(in rootround, of in een volgende ronde, via addqualifier) KAN GEEN INVLOED HEBBEN OP HET AANTAL QUALIFYGROUPS
     * B een plek verwijderen(in rootround, of in een volgende ronde, via removequalifier)
     * C het aantal poules verkleinen met 1     - refillRound, update horpoules and update previousround qualifyrules
     * D C het aantal poules vergroten met 1    - refillRound, update horpoules and update previousround qualifyrules
     *
     * C, D hebben geen invloed op het aantal plekken in volgende ronden
     */
    // in root only a poule can be added
    // options:
    // 3,3 => 4, 5
    public function addPlaceToRootRound(Round $rootRound): Place
    {
        $newNrOfPlaces = $rootRound->getNrOfPlaces() + 1;
        $nrOfPoules = $rootRound->getPoules()->count();
        $this->placeRanges?->validate($newNrOfPlaces, $nrOfPoules);

        $this->horPouleCreator->remove($rootRound);
        $this->rulesCreator->remove($rootRound);

        // begin editing
        $rootRound->addPlace();
        // end editing
        $this->horPouleCreator->create($rootRound);
        $this->rulesCreator->create($rootRound, null);

        return $rootRound->getFirstPlace(QualifyTarget::Losers);
    }

    public function removePlaceFromRootRound(Round $rootRound): void
    {
        if ($rootRound->getNrOfDropoutPlaces() <= 0) {
            throw new Exception('de deelnemer kan niet verwijderd worden, omdat alle deelnemer naar de volgende ronde gaan', E_ERROR);
        }
        $newNrOfPlaces = $rootRound->getNrOfPlaces() - 1;
        $this->placeRanges?->validate($newNrOfPlaces, $rootRound->getPoules()->count());

        $this->horPouleCreator->remove($rootRound);
        $this->rulesCreator->remove($rootRound);
        // begin editing
        $rootRound->removePlace();
        // end editing
        $this->horPouleCreator->create($rootRound);
        $this->rulesCreator->create($rootRound, null);
    }

    public function addPouleToRootRound(Round $rootRound): Poule
    {
        $lastPoule = $rootRound->getFirstPoule();
        $newNrOfPlaces = $rootRound->getNrOfPlaces() + $lastPoule->getPlaces()->count();
        $this->validate($newNrOfPlaces, $rootRound->getPoules()->count() + 1);

        $this->horPouleCreator->remove($rootRound);
        $this->rulesCreator->remove($rootRound);
        // begin editing
        $rootRound->addPoule();
        $this->addChildRoundPlacesForNonCrossFinals($rootRound);
        $this->horPouleCreator->create($rootRound);
        $this->rulesCreator->create($rootRound, null);
        // end editing
        return $rootRound->getLastPoule();
    }

    private function addChildRoundPlacesForNonCrossFinals(Round $parentRound): void
    {
        foreach ([QualifyTarget::Winners, QualifyTarget::Losers] as $qualifyTarget) {
            $qualifyGroups = $parentRound->getTargetQualifyGroups($qualifyTarget);
            if (count($qualifyGroups) < 2) {
                continue;
            }

            $nrOfPoulesBeforeAdd = count($parentRound->getPoules()) - 1;

            $maxNrOfPlaces = $parentRound->getNrOfPlaces();
            $currentNrOfPlaces = 0;
            foreach ($qualifyGroups as $qualifyGroup) {
                $childRound = $qualifyGroup->getChildRound();
                $this->horPouleCreator->remove($childRound);
                $this->rulesCreator->remove($childRound);

                if ($maxNrOfPlaces - $currentNrOfPlaces < $this->getMinPlacesPerPouleSmall()) {
                    $qualifyGroup->detach();
                    continue;
                }

                $nrOfPlacesToAdd = (int)($childRound->getNrOfPlaces() / $nrOfPoulesBeforeAdd);
                for ($i = 1; $i <= $nrOfPlacesToAdd; $i++) {
                    $childRound->addPlace();
                }
                $currentNrOfPlaces += $childRound->getNrOfPlaces();
                $this->horPouleCreator->create($childRound);
                $this->rulesCreator->create($childRound, null);
            }
        }
    }

    public function removePouleFromRootRound(Round $rootRound): void
    {
        $poules = $rootRound->getPoules();
        if ($poules->count() <= 1) {
            throw new Exception('er moet minimaal 1 poule overblijven', E_ERROR);
        }

        $places = array_values($rootRound->getLastPoule()->getPlaces()->toArray());
        $nrOfPlacesToRemoveMap = $this->removalValidator->getNrOfPlacesToRemoveMap($rootRound, $places);
        $this->removalValidator->willStructureBeValid(
            $rootRound,
            $nrOfPlacesToRemoveMap,
            $this->getMinPlacesPerPouleSmall()
        );

        $this->horPouleCreator->remove($rootRound);
        $this->rulesCreator->remove($rootRound);
        // begin editing
        $rootRound->removePoule();
        $this->removeChildRoundPlaces($rootRound, $nrOfPlacesToRemoveMap);
        // end editing
        $this->horPouleCreator->create($rootRound);
        $this->rulesCreator->create($rootRound, null);
    }


    /**
     * @param Round $parentRound
     * @param array<string, int> $nrOfPlacesToRemoveMap
     */
    private function removeChildRoundPlaces(Round $parentRound, array $nrOfPlacesToRemoveMap): void
    {
        foreach ([QualifyTarget::Winners, QualifyTarget::Losers] as $qualifyTarget) {
            $qualifyGroups = $parentRound->getTargetQualifyGroups($qualifyTarget);
            if (count($qualifyGroups) < 2) { // kan weg?
                continue;
            }

            foreach ($qualifyGroups as $qualifyGroup) {
                $qualifyGroupIdx = $this->removalValidator->getQualifyGroupIndex($qualifyGroup);
                $nrOfPlacesToRemove = $nrOfPlacesToRemoveMap[$qualifyGroupIdx];

                $childRound = $qualifyGroup->getChildRound();

                while ($nrOfPlacesToRemove--) {
                    $this->removePlaceFromRound($childRound, false);
                }
            }
        }
    }

    public function incrementNrOfPoules(Round $round): void
    {
        $this->validate($round->getNrOfPlaces(), $round->getPoules()->count() + 1);

        $this->horPouleCreator->remove($round);
        $this->rulesCreator->remove($round);
        // begin editing
        $nrOfPlacesToRemove = $round->addPoule()->getPlaces()->count();
        for ($i = 0; $i < $nrOfPlacesToRemove; $i++) {
            $round->removePlace();
        }
        $this->addChildRoundPlacesForNonCrossFinals($round);
        // end editing
        $this->horPouleCreator->create($round);
        $this->rulesCreator->create($round, $round->getParent());
    }

    public function decrementNrOfPoules(Round $round): void
    {
        $poules = $round->getPoules();
        if ($poules->count() <= 1) {
            throw new Exception('er moet minimaal 1 poule overblijven', E_ERROR);
        }

        $this->horPouleCreator->remove($round);
        $this->rulesCreator->remove($round);
        // begin editing
        $nrOfPlacesToAdd = $round->removePoule()->getPlaces()->count();
        for ($i = 0; $i < $nrOfPlacesToAdd; $i++) {
            $round->addPlace();
        }
        // end editing
        $this->horPouleCreator->create($round);
        $this->rulesCreator->create($round, $round->getParent());
    }

    public function updateDistribution(QualifyGroup $qualifyGroup, QualifyDistribution $distribution): void {

        $parentRound = $qualifyGroup->getParentRound();
        $this->horPouleCreator->remove($parentRound);
        $this->rulesCreator->remove($parentRound);

        // begin editing
        $qualifyGroup->setDistribution($distribution);

        // end editing
        $this->horPouleCreator->create($parentRound);
        $this->rulesCreator->create($parentRound, $qualifyGroup->getChildRound());
    }

    public function addQualifiers(
        Round $parentRound,
        QualifyTarget $qualifyTarget,
        int $nrOfToPlacesToAdd,
        QualifyDistribution $distribution,
        int|null $maxNrOfPoulePlaces = null
    ): void {
        $nrOfPlaces = $parentRound->getNrOfPlaces();
        $nrOfToPlaces = $parentRound->getNrOfPlacesChildren();
        if (($nrOfToPlaces + $nrOfToPlacesToAdd) > $nrOfPlaces) {
            throw new Exception(
                'er mogen maximaal ' . ($nrOfPlaces - $nrOfToPlaces) . ' deelnemers naar de volgende ronde'
            );
        }
        // begin editing
        $qualifyGroup = $parentRound->getBorderQualifyGroup($qualifyTarget);
        if ($qualifyGroup === null) {
            $minNrOfPlacesPerPoule = $this->getMinPlacesPerPouleSmall();
            if ($nrOfToPlacesToAdd < $minNrOfPlacesPerPoule) {
                throw new \Exception('er moeten minimaal ' . $minNrOfPlacesPerPoule . ' deelnemers naar de volgende ronde, vanwege het aantal deelnemers per wedstrijd', E_ERROR);
            }
            $newStructure = new BalancedPouleStructure([$minNrOfPlacesPerPoule]);
            $this->placeRanges?->validateStructure($newStructure);
            $this->horPouleCreator->remove($parentRound);
            $this->rulesCreator->remove($parentRound);
            // begin editing
            $qualifyGroup = $this->addChildRoundHelper($parentRound, $qualifyTarget, $newStructure, $distribution);
            $nrOfToPlacesToAdd -= $minNrOfPlacesPerPoule;
            $childRound = $qualifyGroup->getChildRound();
            while ($nrOfToPlacesToAdd-- > 0) {
                $childRound->addPlace();
            }
            // end editing
            $this->horPouleCreator->create($parentRound, $childRound);
            $this->rulesCreator->create($childRound, $parentRound);
        } else {
            $childRound = $qualifyGroup->getChildRound();
            $this->validate($childRound->getNrOfPlaces() + $nrOfToPlacesToAdd, $childRound->getPoules()->count());
            $this->horPouleCreator->remove($childRound);
            $this->rulesCreator->remove($parentRound, $childRound);
            // begin editing
            $pouleAdded = false;
            while ($nrOfToPlacesToAdd-- > 0) {
                $pouleStructure = $childRound->createPouleStructure();
                if ($maxNrOfPoulePlaces > 0 && $this->canAddPouleByAddingOnePlace(
                        $pouleStructure,
                        $maxNrOfPoulePlaces
                    )) {
                    $nrOfPlacesToRemove = count($childRound->addPoule()->getPlaces());
                    for ($i = 0; $i < $nrOfPlacesToRemove - 1; $i++) {
                        $childRound->removePlace();
                    }
                    $pouleAdded = true;
                } else {
                    $childRound->addPlace();
                }
            }
            if ($pouleAdded) {
                $this->addChildRoundPlacesForNonCrossFinals($childRound);
            }
            // end editing
            $this->horPouleCreator->create($childRound);
            $this->rulesCreator->create($childRound, $parentRound);
        }
    }

    protected function canAddPouleByAddingOnePlace(
        BalancedPouleStructure $pouleStructure,
        int $maxNrOfPoulePlaces
    ): bool {
        $nrOfPlacesForNewPoule = 0;
        foreach ($pouleStructure->toArray() as $nrOfPoulePlaces) {
            $nrOfPoulePlacesForNewPoule = $nrOfPoulePlaces - $maxNrOfPoulePlaces;
            if ($nrOfPoulePlacesForNewPoule > 0) {
                $nrOfPlacesForNewPoule += $nrOfPoulePlacesForNewPoule;
            }
        }
        return ($nrOfPlacesForNewPoule + 1) >= $maxNrOfPoulePlaces;
    }

    public function removeQualifier(Round $parentRound, QualifyTarget $qualifyTarget): bool
    {
        $qualifyGroup = $parentRound->getBorderQualifyGroup($qualifyTarget);
        if ($qualifyGroup === null) {
            return false;
        }
        $childRound = $qualifyGroup->getChildRound();
        $this->rulesCreator->remove($parentRound);
        // begin editing
        if ($childRound->getNrOfPlaces() <= 2) {
            $qualifyGroup->detach();
        } else {
            $this->removePlaceFromRound($childRound, false);
        }
        // end editing
        $this->rulesCreator->create($parentRound, null);
        return true;
    }

    private function fillRound(Round $round, BalancedPouleStructure $pouleStructure): void
    {
        foreach ($pouleStructure->toArray() as $nrOfPlaces) {
            $poule = new Poule($round);
            for ($placeNr = 1; $placeNr <= $nrOfPlaces; $placeNr++) {
                new Place($poule);
            }
        }
    }

    protected function getNrOfQualifiersPrevious(HorizontalSingleQualifyRule|VerticalSingleQualifyRule $singleRule): int
    {
        return $singleRule->getNrOfMappings() + $singleRule->getNrOfToPlacesTargetSide(QualifyTarget::Winners);
    }

    protected function getNrOfQualifiersNext(HorizontalSingleQualifyRule|VerticalSingleQualifyRule $singleRule): int
    {
        return $singleRule->getNrOfMappings() + $singleRule->getNrOfToPlacesTargetSide(QualifyTarget::Losers);
    }

    protected function getRoot(Round $round): Round
    {
        $parent = $round->getParent();
        return $parent !== null ? $this->getRoot($parent) : $round;
    }

    public function createBalanced(int $nrOfPlaces, int $nrOfPoules): BalancedPouleStructure
    {
        $pouleStructureCreator = new BalancedPouleStructureCreator();
        return $pouleStructureCreator->createBalanced($nrOfPlaces, $nrOfPoules);
    }

    public function isQualifyGroupSplittableAt(HorizontalSingleQualifyRule|VerticalSingleQualifyRule $singleRule): bool
    {
        $next = $singleRule->getNext();
        if ($next === null) {
            return false;
        }
        return $this->getNrOfQualifiersPrevious($singleRule) >= 2
            && $this->getNrOfQualifiersNext($next) >= 2;
    }

    // horizontalPoule is split-points, from which qualifyGroup
    public function splitQualifyGroupFrom(QualifyGroup $qualifyGroup, HorizontalSingleQualifyRule|VerticalSingleQualifyRule $singleRule): void
    {
        $parentRound = $qualifyGroup->getParentRound();
        $nrOfToPlaces = $singleRule->getNrOfMappings() + $singleRule->getNrOfToPlacesTargetSide(QualifyTarget::Winners);
        $borderSideNrOfToPlaces = $singleRule->getNrOfToPlacesTargetSide(QualifyTarget::Losers);
        if ($nrOfToPlaces < 2 || $borderSideNrOfToPlaces < 2) {
            throw new Exception('de kwalificatiegroep is niet splitsbaar', E_ERROR);
        }
        $childRound = $qualifyGroup->getChildRound();
        $this->rulesCreator->remove($parentRound);
        // begin editing

        // STEP 1 : insert new round
        $newQualifyGroup = $this->insertAfterQualifyGroup($parentRound, $qualifyGroup);
        // STEP 2 : update existing qualifyGroup
        while ($childRound->getNrOfPlaces() > $nrOfToPlaces) {
            $this->removePlaceFromRound($childRound);
        }
        // STEP 3 : fill new qualifyGroup
        $newChildRound = $newQualifyGroup->getChildRound();
        $nrOfPoulePlaces = $childRound->getFirstPoule()->getPlaces()->count();
        $newNrOfPoules = $this->calculateNrOfPoulesInsertedQualifyGroup($borderSideNrOfToPlaces, $nrOfPoulePlaces);
        $balancedPouleStructure = $this->createBalanced($borderSideNrOfToPlaces, $newNrOfPoules);
        $this->fillRound($newChildRound, $balancedPouleStructure);
        $this->horPouleCreator->create($newChildRound);
        // end editing
        $this->rulesCreator->create($parentRound, null);
    }

    // horizontalPoule is split-points, from which qualifyGroup
    protected function insertAfterQualifyGroup(Round $parentRound, QualifyGroup $qualifyGroup): QualifyGroup
    {
        $childRound = $qualifyGroup->getChildRound();

        $newQualifyGroup = new QualifyGroup(
            $parentRound,
            $qualifyGroup->getTarget(),
            $childRound->getStructureCell(),
            $qualifyGroup->getNumber() + 1
        );
        $newQualifyGroup->setDistribution($qualifyGroup->getDistribution());
        $this->renumber($parentRound, $qualifyGroup->getTarget());
        return $newQualifyGroup;
    }

    protected function calculateNrOfPoulesInsertedQualifyGroup(int $nrOfToPlaces, int $nrOfPoulePlaces): int
    {
        $nrOfPoules = 0;
        while (($nrOfToPlaces - $nrOfPoulePlaces) >= 0) {
            $nrOfPoules++;
            $nrOfToPlaces -= $nrOfPoulePlaces;
        }
        if ($nrOfToPlaces === 1 && $nrOfPoules > 1) {
            $nrOfPoules--;
        }
        return $nrOfPoules;
    }

    // recalc horPoules and rules only downwards
    protected function removePlaceFromRound(Round $round, bool $canHaveZeroDropoutPlaces = true): void
    {
        $this->horPouleCreator->remove($round);
        $this->rulesCreator->remove($round);
        // begin editing
        $nrOfPlacesRemoved = $round->removePlace();
        if ($nrOfPlacesRemoved > 1 && $round->getPoules()->count() >= 1) {
            $round->addPlace();
        }
        $this->horPouleCreator->create($round);
        $nrOfDropoutPlaces = $round->getNrOfDropoutPlaces();
        if ($nrOfDropoutPlaces < 0 || (!$canHaveZeroDropoutPlaces && $round->getNrOfDropoutPlaces() === 0)) {
            $losersBorderQualifyGroup = $round->getBorderQualifyGroup(QualifyTarget::Losers);
            $childQualifyTarget = $losersBorderQualifyGroup !== null ? QualifyTarget::Losers : QualifyTarget::Winners;
            $this->removeQualifier($round, $childQualifyTarget);
        } else {
            $this->rulesCreator->create($round, null);
        }
    }

    public function areQualifyGroupsMergable(QualifyGroup $previous, QualifyGroup $current): bool
    {
        return $previous->getTarget() === $current->getTarget()
            && $previous->getNumber() + 1 === $current->getNumber();
    }

    public function mergeQualifyGroups(QualifyGroup $firstQualifyGroup, QualifyGroup $secondQualifyGroup): void
    {
        $parentRound = $firstQualifyGroup->getParentRound();
        $childRound = $firstQualifyGroup->getChildRound();
        $this->horPouleCreator->remove($childRound);
        $this->rulesCreator->remove($parentRound);
        // begin editing
        $nrOfPlacesToAdd = $secondQualifyGroup->getChildRound()->getNrOfPlaces();
        $secondQualifyGroup->detach();
        $this->renumber($parentRound, $secondQualifyGroup->getTarget());
        for ($counter = 0; $counter < $nrOfPlacesToAdd; $counter++) {
            $firstQualifyGroup->getChildRound()->addPlace();
        }
        // end editing
        $this->horPouleCreator->create($childRound);
        $this->rulesCreator->create($parentRound, null);
    }

    protected function renumber(Round $round, QualifyTarget $qualifyTarget): void
    {
        $number = 1;
        foreach ($round->getTargetQualifyGroups($qualifyTarget) as $qualifyGroup) {
            $qualifyGroup->setNumber($number++);
        }
    }

    public function validate(int $nrOfPlaces, int $nrOfPoules): bool
    {
        if ($this->placeRanges !== null) {
            return $this->placeRanges->validate($nrOfPlaces, $nrOfPoules);
        }
        if ($nrOfPlaces < MinNrOfPlacesCalculator::MinNrOfPlacesPerPoule) {
            throw new \Exception(
                'het minimaal aantal deelnemers is ' . MinNrOfPlacesCalculator::MinNrOfPlacesPerPoule,
                E_ERROR
            );
        }
        return true;
    }

    public function getMinPlacesPerPouleSmall(): int
    {
        if ($this->placeRanges === null) {
            return MinNrOfPlacesCalculator::MinNrOfPlacesPerPoule;
        }
        return $this->placeRanges->placesPerPouleSmall->getMin();
    }

    /* MOVE TO FCTOERNOOI DEFAULTSERVICE
        public function getDefaultNrOfPoules(int $nrOfPlaces): int
        {
            $this->checkRanges($nrOfPlaces);
            switch ($nrOfPlaces) {
                case 2:
                case 3:
                case 4:
                case 5:
                case 7:
                {
                    return 1;
                }
                case 6:
                case 8:
                case 10:
                case 11:
                {
                    return 2;
                }
                case 9:
                case 12:
                case 13:
                case 14:
                case 15:
                {
                    return 3;
                }
                case 16:
                case 17:
                case 18:
                case 19:
                {
                    return 4;
                }
                case 20:
                case 21:
                case 22:
                case 23:
                case 25:
                {
                    return 5;
                }
                case 24:
                case 26:
                case 29:
                case 30:
                case 33:
                case 34:
                case 36:
                {
                    return 6;
                }
                case 28:
                case 31:
                case 35:
                case 37:
                case 38:
                case 39:
                {
                    return 7;
                }
                case 27:
                {
                    return 9;
                }
            }
            return 8;
        }
    */
}
