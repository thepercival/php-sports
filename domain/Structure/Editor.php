<?php

namespace Sports\Structure;

use Exception;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Structure;
use Sports\Structure as StructureBase;
use SportsHelpers\PlaceRanges;
use SportsHelpers\PouleStructure\Balanced as BalancedPouleStructure;
use Sports\Competition;
use Sports\Place;
use Sports\Poule;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Planning\Config\Service as PlanningConfigService;
use Sports\Qualify\Rule\Creator as QualifyRuleCreator;
use Sports\Qualify\Rule\Single as QualifyRuleSingle;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Poule\Horizontal\Creator as HorizontalPouleCreator;
use Sports\Competition\Sport\Service as CompetitionSportService;
use SportsHelpers\PouleStructure\BalancedCreator as BalancedPouleStructureCreator;

class Editor
{
    private HorizontalPouleCreator $horPouleCreator;
    private QualifyRuleCreator $rulesCreator;
    private PlaceRanges|null $placeRanges = null;

    public function __construct(
        private CompetitionSportService $competitionSportService,
        private PlanningConfigService $planningConfigService
    ) {
        $this->horPouleCreator = new HorizontalPouleCreator();
        $this->rulesCreator = new QualifyRuleCreator();
    }

    public function setPlaceRanges(PlaceRanges $placeRanges): void
    {
        $this->placeRanges = $placeRanges;
    }

    /**
     * @param Competition $competition
     * @param list<int> $pouleStructure
     * @return StructureBase
     * @throws Exception
     */
    public function create(Competition $competition, array $pouleStructure): Structure
    {
        $balancedPouleStructure = new BalancedPouleStructure(...$pouleStructure);
        // begin editing
        $firstRoundNumber = new RoundNumber($competition);
        $this->planningConfigService->createDefault($firstRoundNumber);

        $rootRound = new Round($firstRoundNumber, null);
        $this->fillRound($rootRound, $balancedPouleStructure);
        $structure = new Structure($firstRoundNumber, $rootRound);
        foreach ($competition->getSports() as $competitionSport) {
            $this->competitionSportService->addToStructure($competitionSport, $structure);
        }
        // end editing
        $this->horPouleCreator->create($rootRound);
        $this->rulesCreator->create($rootRound);
        return $structure;
    }

    /**
     * @param Round $parentRound
     * @param string $qualifyTarget
     * @param list<int> $pouleStructure
     * @return Round
     * @throws Exception
     */
    public function addChildRound(Round $parentRound, string $qualifyTarget, array $pouleStructure): Round
    {
        $balancedPouleStructure = new BalancedPouleStructure(...$pouleStructure);
        $this->placeRanges?->validateStructure($balancedPouleStructure);
        $this->rulesCreator->remove($parentRound);
        // begin editing

        $qualifyGroup = $this->addChildRoundHelper(
            $parentRound,
            $qualifyTarget,
            $balancedPouleStructure
        );
        // end editing
        $this->horPouleCreator->create($qualifyGroup->getChildRound());
        $this->rulesCreator->create($parentRound);
        return $qualifyGroup->getChildRound();
    }

    private function addChildRoundHelper(
        Round $parentRound,
        string $qualifyTarget,
        BalancedPouleStructure $pouleStructure
    ): QualifyGroup {
        $nextRoundNumber = $parentRound->getNumber()->getNext();
        if ($nextRoundNumber === null) {
            $nextRoundNumber = $parentRound->getNumber()->createNext();
        }
        $qualifyGroup = new QualifyGroup($parentRound, $qualifyTarget, $nextRoundNumber);
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
        $this->rulesCreator->create($rootRound);
    
        return $rootRound->getFirstPlace(QualifyTarget::LOSERS);
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
        $this->rulesCreator->create($rootRound);
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
        // end editing
        $this->horPouleCreator->create($rootRound);
        $this->rulesCreator->create($rootRound);

        return $rootRound->getLastPoule();
    }

    public function removePouleFromRootRound(Round $rootRound): void
    {
        $poules = $rootRound->getPoules();
        if ($poules->count() <= 1) {
            throw new Exception('er moet minimaal 1 poule overblijven', E_ERROR);
        }
        $lastPoule = $rootRound->getLastPoule();
        $newNrOfPlaces = $rootRound->getNrOfPlaces() - $lastPoule->getPlaces()->count();

        if ($newNrOfPlaces < $rootRound->getNrOfPlacesChildren()) {
            throw new Exception('de poule kan niet verwijderd worden, omdat er te weinig deelnemers '
                            . 'overblijven om naar de volgende ronde gaan', E_ERROR);
        }

        $this->horPouleCreator->remove($rootRound);
        $this->rulesCreator->remove($rootRound);
        // begin editing
        $rootRound->removePoule();
        // end editing
        $this->horPouleCreator->create($rootRound);
        $this->rulesCreator->create($rootRound);
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
        // end editing
        $this->horPouleCreator->create($round);
        $this->rulesCreator->create($round->getParent(), $round);
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
        $this->rulesCreator->create($round->getParent(), $round);
    }

    public function addQualifiers(Round $parentRound, string $qualifyTarget, int $nrOfToPlacesToAdd): void
    {
        $nrOfPlaces = $parentRound->getNrOfPlaces();
        $nrOfToPlaces = $parentRound->getNrOfPlacesChildren();
        if (($nrOfToPlaces + $nrOfToPlacesToAdd) > $nrOfPlaces) {
            throw new Exception('er mogen maximaal ' . ($nrOfPlaces - $nrOfToPlaces) . ' deelnemers naar de volgende ronde');
        }
        // begin editing
        $qualifyGroup = $parentRound->getBorderQualifyGroup($qualifyTarget);
        if ($qualifyGroup === null) {
            $minNrOfPlacesPerPoule = $this->getMinPlacesPerPouleSmall();
            if ($nrOfToPlacesToAdd < $minNrOfPlacesPerPoule) {
                throw new \Exception('er moeten minimaal ' . $minNrOfPlacesPerPoule . ' deelnemers naar de volgende ronde, vanwege het aantal deelnemers per wedstrijd', E_ERROR);
            }
            $newStructure = new BalancedPouleStructure($minNrOfPlacesPerPoule);
            $this->placeRanges?->validateStructure($newStructure);
            $this->horPouleCreator->remove($parentRound);
            $this->rulesCreator->remove($parentRound);
            // begin editing
            $qualifyGroup = $this->addChildRoundHelper($parentRound, $qualifyTarget, $newStructure);
            $nrOfToPlacesToAdd -= $minNrOfPlacesPerPoule;
            $childRound = $qualifyGroup->getChildRound();
            while ($nrOfToPlacesToAdd-- > 0) {
                $childRound->addPlace();
            }
            // end editing
            $this->horPouleCreator->create($parentRound, $childRound);
            $this->rulesCreator->create($parentRound, $childRound);
        } else {
            $childRound = $qualifyGroup->getChildRound();
            $this->validate($childRound->getNrOfPlaces() + $nrOfToPlacesToAdd, $childRound->getPoules()->count());
            $this->horPouleCreator->remove($childRound);
            $this->rulesCreator->remove($parentRound, $childRound);
            // begin editing
            while ($nrOfToPlacesToAdd-- > 0) {
                $childRound->addPlace();
            }
            // end editing
            $this->horPouleCreator->create($childRound);
            $this->rulesCreator->create($parentRound, $childRound);
        }
    }

    public function removeQualifier(Round $parentRound, string $qualifyTarget): bool
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
            $this->removePlaceFromRound($childRound);
        }
        // end editing
        $this->rulesCreator->create($parentRound);
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

    protected function getNrOfQualifiersPrevious(QualifyRuleSingle $singleRule): int
    {
        return $singleRule->getNrOfToPlaces() + $singleRule->getNrOfToPlacesTargetSide(QualifyTarget::WINNERS);
    }

    protected function getNrOfQualifiersNext(QualifyRuleSingle $singleRule): int
    {
        return $singleRule->getNrOfToPlaces() + $singleRule->getNrOfToPlacesTargetSide(QualifyTarget::LOSERS);
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

    public function isQualifyGroupSplittableAt(QualifyRuleSingle $singleRule): bool
    {
        $next = $singleRule->getNext();
        if ($next === null) {
            return false;
        }
        return $this->getNrOfQualifiersPrevious($singleRule) >= 2
            && $this->getNrOfQualifiersNext($next) >= 2;
    }

    // horizontalPoule is split-points, from which qualifyGroup
    public function splitQualifyGroupFrom(QualifyGroup $qualifyGroup, QualifyRuleSingle $singleRule): void
    {
        $parentRound = $qualifyGroup->getParentRound();
        $nrOfToPlaces = $singleRule->getNrOfToPlaces() + $singleRule->getNrOfToPlacesTargetSide(QualifyTarget::WINNERS);
        $borderSideNrOfToPlaces = $singleRule->getNrOfToPlacesTargetSide(QualifyTarget::LOSERS);
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
        $this->rulesCreator->create($parentRound);
    }

    // horizontalPoule is split-points, from which qualifyGroup
    protected function insertAfterQualifyGroup(Round $parentRound, QualifyGroup $qualifyGroup): QualifyGroup
    {
        $childRound = $qualifyGroup->getChildRound();

        $newQualifyGroup = new QualifyGroup(
            $parentRound,
            $qualifyGroup->getTarget(),
            $childRound->getNumber(),
            $qualifyGroup->getNumber() + 1
        );
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
    protected function removePlaceFromRound(Round $round): void
    {
        $this->horPouleCreator->remove($round);
        $this->rulesCreator->remove($round);
        // begin editing
        $nrOfPlacesRemoved = $round->removePlace();
        if ($nrOfPlacesRemoved > 1 && $round->getPoules()->count() >= 1) {
            $round->addPlace();
        }
        $this->horPouleCreator->create($round);
        // === because nrOfQualifiers should always go down with at leat one
        if ($round->getNrOfDropoutPlaces() <= 0) {
            $losersBorderQualifyGroup = $round->getBorderQualifyGroup(QualifyTarget::LOSERS);
            $childQualifyTarget = $losersBorderQualifyGroup !== null ? QualifyTarget::LOSERS : QualifyTarget::WINNERS;
            $this->removeQualifier($round, $childQualifyTarget);
        } else {
            $this->rulesCreator->create($round);
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
        $this->rulesCreator->create($parentRound);
    }

    protected function renumber(Round $round, string $qualifyTarget): void
    {
        $number = 1;
        foreach ($round->getTargetQualifyGroups($qualifyTarget) as $qualifyGroup) {
            $qualifyGroup->setNumber($number++);
        }
    }

    public function validate(int $nrOfPlaces, int $nrOfPoules): bool {
        if ($this->placeRanges !== null) {
            return $this->placeRanges->validate($nrOfPlaces, $nrOfPoules);
        }
        if ($nrOfPlaces < PlaceRanges::MinNrOfPlacesPerPoule) {
            throw new \Exception('het minimaal aantal deelnemers is ' . PlaceRanges::MinNrOfPlacesPerPoule, E_ERROR);
        }
        return true;
    }

    public function getMinPlacesPerPouleSmall(): int {
        if( $this->placeRanges === null ) {
            return PlaceRanges::MinNrOfPlacesPerPoule;
        }
        return $this->placeRanges->getPlacesPerPouleSmall()->getMin();
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
