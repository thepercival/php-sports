<?php

namespace Sports\Structure;

use \Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Exception;
use Sports\Qualify\AgainstConfig as QualifyAgainstConfig;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Structure as StructureBase;
use SportsHelpers\PouleStructure\Balanced as BalancedPouleStructure;
use Sports\Competition;
use Sports\Place;
use SportsHelpers\Place\Range as PlaceRange;
use Sports\Poule;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Poule\Horizontal\Creator as HorizontolPouleCreator;
use Sports\Poule\Horizontal\Service as HorizontalPouleService;
use Sports\Planning\Config\Service as PlanningConfigService;
use Sports\Qualify\Rule\Service as QualifyRuleService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Group\Service as QualifyGroupService;
use Sports\Competition\Sport\Service as CompetitionSportService;
use SportsHelpers\PouleStructure;

class Service
{
    private PlanningConfigService $planningConfigService;

    /**
     * @param list<PlaceRange> $placeRanges
     */
    public function __construct(protected array $placeRanges)
    {
        $this->planningConfigService = new PlanningConfigService();
    }

    public function create(Competition $competition, PouleStructure $pouleStructure): StructureBase
    {
        $firstRoundNumber = new RoundNumber($competition);
        $competitionSportService = new CompetitionSportService();
        $this->planningConfigService->createDefault($firstRoundNumber);
        $rootRound = new Round($firstRoundNumber, null);
        $nrOfPoulesToAdd = $pouleStructure->getNrOfPoules(); // nrOfPoules ? nrOfPoules : this.getDefaultNrOfPoules(nrOfPlaces);
        // $nrOfPoulesToAdd = ($nrOfPoules !== null) ? $nrOfPoules : $this->getDefaultNrOfPoules($nrOfPlaces);
        $this->updateRound($rootRound, $pouleStructure->getNrOfPlaces(), $nrOfPoulesToAdd);
        $structure = new StructureBase($firstRoundNumber, $rootRound);
        foreach ($competition->getSports() as $competitionSport) {
            $competitionSportService->addToStructure($competitionSport, $structure);
        }
        $structure->setStructureNumbers();
        return $structure;
    }

    public function removePlaceFromRootRound(Round $round): void
    {
        // console.log('removePoulePlace for round ' + round.getNumberAsValue());
        $nrOfPlaces = $round->getNrOfPlaces();
        if ($nrOfPlaces === $round->getNrOfPlacesChildren()) {
            throw new Exception(
                'de deelnemer kan niet verwijderd worden, omdat alle deelnemer naar de volgende ronde gaan',
                E_ERROR
            );
        }
        $newNrOfPlaces = $nrOfPlaces - 1;
        $this->checkRanges($newNrOfPlaces);
        if (($newNrOfPlaces / $round->getPoules()->count()) < 2) {
            throw new Exception(
                'Er kan geen deelnemer verwijderd worden. De minimale aantal deelnemers per poule is 2.',
                E_ERROR
            );
        }

        $this->updateRound($round, $newNrOfPlaces, $round->getPoules()->count());

        $rootRound = $this->getRoot($round);
        $structure = new StructureBase($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();
    }

    public function addPlaceToRootRound(Round $round): Place
    {
        $newNrOfPlaces = $round->getNrOfPlaces() + 1;
        $nrOfPoules = $round->getPoules()->count();
        $this->checkRanges($newNrOfPlaces, $nrOfPoules);

        $this->updateRound($round, $newNrOfPlaces, $round->getPoules()->count());

        $rootRound = $this->getRoot($round);
        $structure = new StructureBase($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();

        return $round->getFirstPlace(QualifyGroup::LOSERS);
    }

    public function removePoule(Round $round, bool $modifyNrOfPlaces = null): void
    {
        $lastPoule = $round->getPoules()->last();
        if ($lastPoule === false) {
            throw new Exception('er moet minimaal 1 poule overblijven', E_ERROR);
        }
        $newNrOfPlaces = $round->getNrOfPlaces();
        if ($modifyNrOfPlaces === true) {
            $newNrOfPlaces -= $lastPoule->getPlaces()->count();
        }

        if ($newNrOfPlaces < $round->getNrOfPlacesChildren()) {
            throw new Exception(
                'de poule kan niet verwijderd worden, omdat er te weinig deelnemers overblijven om naar de volgende ronde gaan',
                E_ERROR
            );
        }

        $this->updateRound($round, $newNrOfPlaces, $round->getPoules()->count() - 1);
        if (!$round->isRoot()) {
            $qualifyRuleService = new QualifyRuleService($round);
            $qualifyRuleService->recreateFrom();
        }

        $rootRound = $this->getRoot($round);
        $structure = new StructureBase($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();
    }

    public function addPoule(Round $round, bool $modifyNrOfPlaces = null): Poule
    {
        $poules = $round->getPoules();
        $lastPoule = $poules->last();
        if ($lastPoule === false) {
            throw new Exception('er moet minimaal 1 poule zijn', E_ERROR);
        }
        $newNrOfPlaces = $round->getNrOfPlaces();
        if ($modifyNrOfPlaces === true) {
            $newNrOfPlaces -= $lastPoule->getPlaces()->count();
            $this->checkRanges($newNrOfPlaces, $poules->count());
        }
        $this->updateRound($round, $newNrOfPlaces, $poules->count() + 1);
        if (!$round->isRoot()) {
            $qualifyRuleService = new QualifyRuleService($round);
            $qualifyRuleService->recreateFrom();
        }

        $rootRound = $this->getRoot($round);
        $structure = new StructureBase($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();

        $lastPoule = $round->getPoules()->last();
        if ($lastPoule === false) {
            throw new Exception('er moet minimaal 1 poule zijn', E_ERROR);
        }
        return $lastPoule;
    }

    public function removeQualifier(Round $round, int $winnersOrLosers): void
    {
        $nrOfPlaces = $round->getNrOfPlacesChildren($winnersOrLosers);
        $borderQualifyGroup = $round->getBorderQualifyGroup($winnersOrLosers);
        $newNrOfPlaces = $nrOfPlaces - 1;
        if ($borderQualifyGroup !== null && $borderQualifyGroup->getNrOfQualifiers() === 2) {
            $newNrOfPlaces--;
        }
        $this->updateQualifyGroups($round, $winnersOrLosers, $newNrOfPlaces);

        $qualifyRuleService = new QualifyRuleService($round);
        // qualifyRuleService.recreateFrom();
        $qualifyRuleService->recreateTo();

        $rootRound = $this->getRoot($round);
        $structure = new StructureBase($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();
    }

    public function addQualifiers(Round $round, int $winnersOrLosers, int $nrOfQualifiers): void
    {
        if ($round->getBorderQualifyGroup($winnersOrLosers) === null) {
            if ($nrOfQualifiers < 2) {
                throw new Exception("Voeg miniaal 2 gekwalificeerden toe", E_ERROR);
            }
            $nrOfQualifiers--;
        }
        for ($qualifier = 0; $qualifier < $nrOfQualifiers; $qualifier++) {
            $this->addQualifier($round, $winnersOrLosers);
        }
    }

    public function addQualifier(Round $round, int $winnersOrLosers): void
    {
        $nrOfPlaces = $round->getNrOfPlacesChildren($winnersOrLosers);
        $placesToAdd = ($nrOfPlaces === 0 ? 2 : 1);

        if (($round->getNrOfPlacesChildren() + $placesToAdd) > $round->getNrOfPlaces()) {
            throw new Exception(
                'er mogen maximaal ' . $round->getNrOfPlacesChildren() . ' deelnemers naar de volgende ronde',
                E_ERROR
            );
        }

        $newNrOfPlaces = $nrOfPlaces + $placesToAdd;
        $this->updateQualifyGroups($round, $winnersOrLosers, $newNrOfPlaces);

        $qualifyRuleService = new QualifyRuleService($round);
        $qualifyRuleService->recreateTo();

        $rootRound = $this->getRoot($round);
        $structure = new StructureBase($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();
    }

    public function isQualifyGroupSplittable(HorizontalPoule $previous, HorizontalPoule $current): bool
    {
        if ($previous->getQualifyGroup() !== null || $previous->getQualifyGroup() !== $current->getQualifyGroup()) {
            return false;
        }
        if ($current->isBorderPoule() && $current->getNrOfQualifiers() < 2) {
            return false;
        }
        if ($this->getNrOfQualifiersPrevious($previous) < 2 || $this->getNrOfQualifiersNext($current) < 2) {
            return false;
        }
        return true;
    }

    protected function getNrOfQualifiersPrevious(HorizontalPoule $horPoule): int
    {
        return $this->getNrOfQualifiersRecursive($horPoule, 0, false);
    }

    protected function getNrOfQualifiersNext(HorizontalPoule $horPoule): int
    {
        return $this->getNrOfQualifiersRecursive($horPoule, 0, true);
    }

    protected function getNrOfQualifiersRecursive(HorizontalPoule $horPoule, int $nrOfQualifiers, bool $add): int
    {
        $nrOfQualifiers += $horPoule->getNrOfQualifiers();
        $nextHorPoule = $horPoule->getRound()->getHorizontalPoule(
            $horPoule->getWinnersOrLosers(),
            $horPoule->getNumber() + ($add ? 1 : -1)
        );
        if ($nextHorPoule === null) {
            return $nrOfQualifiers;
        }
        return $this->getNrOfQualifiersRecursive($nextHorPoule, $nrOfQualifiers, $add);
    }

    public function splitQualifyGroup(QualifyGroup $qualifyGroup, HorizontalPoule $pouleOne, HorizontalPoule $pouleTwo): void
    {
        if (!$this->isQualifyGroupSplittable($pouleOne, $pouleTwo)) {
            throw new Exception('de kwalificatiegroepen zijn niet splitsbaar', E_ERROR);
        }
        $round = $qualifyGroup->getRound();

        $firstHorPoule = $pouleOne->getNumber() <= $pouleTwo->getNumber() ? $pouleOne : $pouleTwo;
        $secondHorPoule = ($firstHorPoule === $pouleOne) ? $pouleTwo : $pouleOne;

        $nrOfPlacesChildrenBeforeSplit = $round->getNrOfPlacesChildren($qualifyGroup->getWinnersOrLosers());
        $qualifyGroupService = new QualifyGroupService($this);
        $qualifyGroupService->splitFrom($secondHorPoule);

        $this->updateQualifyGroups($round, $qualifyGroup->getWinnersOrLosers(), $nrOfPlacesChildrenBeforeSplit);

        $qualifyRuleService = new QualifyRuleService($round);
        $qualifyRuleService->recreateTo();

        $rootRound = $this->getRoot($round);
        $structure = new StructureBase($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();
    }

    public function areQualifyGroupsMergable(QualifyGroup $previous, QualifyGroup $current): bool
    {
        return ($previous->getWinnersOrLosers() !== QualifyGroup::DROPOUTS
            && $previous->getWinnersOrLosers() === $current->getWinnersOrLosers() && $previous !== $current);
    }

    public function mergeQualifyGroups(QualifyGroup $qualifyGroupOne, QualifyGroup $qualifyGroupTwo): void
    {
        if (!$this->areQualifyGroupsMergable($qualifyGroupOne, $qualifyGroupTwo)) {
            throw new Exception('de kwalificatiegroepen zijn niet te koppelen', E_ERROR);
        }
        $round = $qualifyGroupOne->getRound();
        $winnersOrLosers = $qualifyGroupOne->getWinnersOrLosers();

        $firstQualifyGroup = $qualifyGroupOne->getNumber() <= $qualifyGroupTwo->getNumber(
        ) ? $qualifyGroupOne : $qualifyGroupTwo;
        $secondQualifyGroup = ($firstQualifyGroup === $qualifyGroupOne) ? $qualifyGroupTwo : $qualifyGroupOne;

        $nrOfPlacesChildrenBeforeMerge = $round->getNrOfPlacesChildren($winnersOrLosers);
        $qualifyGroupService = new QualifyGroupService($this);
        $qualifyGroupService->merge($firstQualifyGroup, $secondQualifyGroup);

        $this->updateQualifyGroups($round, $winnersOrLosers, $nrOfPlacesChildrenBeforeMerge);

        $qualifyRuleService = new QualifyRuleService($round);
        $qualifyRuleService->recreateTo();

        $rootRound = $this->getRoot($round);
        $structure = new StructureBase($rootRound->getNumber(), $rootRound);
        $structure->setStructureNumbers();
    }

    /**
     * @return void
     */
    public function updateRound(Round $round, int $newNrOfPlaces, int $newNrOfPoules)
    {
        if ($round->getNrOfPlaces() === $newNrOfPlaces && $newNrOfPoules === $round->getPoules()->count()) {
            return;
        }
        $this->refillRound($round, $newNrOfPlaces, $newNrOfPoules);

        $horizontalPouleService = new HorizontalPouleService($round);
        $horizontalPouleService->recreate();

        foreach ([QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers) {
            $nrOfPlacesWinnersOrLosers = $round->getNrOfPlacesChildren($winnersOrLosers);
            // als aantal plekken minder wordt, dan is nieuwe aantal plekken max. aantal plekken van de ronde
            if ($nrOfPlacesWinnersOrLosers > $newNrOfPlaces) {
                $nrOfPlacesWinnersOrLosers = $newNrOfPlaces;
            }
            $this->updateQualifyGroups($round, $winnersOrLosers, $nrOfPlacesWinnersOrLosers);
        }

        $qualifyRuleService = new QualifyRuleService($round);
        $qualifyRuleService->recreateTo();
    }

    protected function updateQualifyGroups(Round $round, int $winnersOrLosers, int $newNrOfPlacesChildren): void
    {
        $roundNrOfPlaces = $round->getNrOfPlaces();
        if ($newNrOfPlacesChildren > $roundNrOfPlaces) {
            $newNrOfPlacesChildren = $roundNrOfPlaces;
        }
        // dit kan niet direct door de gebruiker maar wel een paar dieptes verder op
        if ($roundNrOfPlaces < 4 && $newNrOfPlacesChildren >= 2) {
            $newNrOfPlacesChildren = 0;
        }

        /** @var list<HorizontolPouleCreator> $horizontalPoulesCreators */
        $horizontalPoulesCreators = [];
        $removedQualifyGroups = $round->getWinnersOrLosersQualifyGroups($winnersOrLosers);
        $round->clearRoundAndQualifyGroups($winnersOrLosers);
        $qualifyGroupNumber = 1;
        while ($newNrOfPlacesChildren > 0) {
            $horizontalPoulesCreator = $this->getHorizontalPouleCreator($removedQualifyGroups, $round, $winnersOrLosers, $newNrOfPlacesChildren);
            $horizontalPoulesCreator->getQualifyGroup()->setNumber($qualifyGroupNumber++);
            $horizontalPoulesCreators[] = $horizontalPoulesCreator;
            $newNrOfPlacesChildren -= $horizontalPoulesCreator->getNrOfQualifiers();
        }
        $horizontalPouleService = new HorizontalPouleService($round);
        $horPoules = array_slice($round->getHorizontalPoules($winnersOrLosers), 0);
        $horizontalPouleService->updateQualifyGroups($horPoules, $horizontalPoulesCreators);

        foreach ($horizontalPoulesCreators as $creator) {
            $newNrOfPoules = $this->calculateNewNrOfPoules($creator->getQualifyGroup(), $creator->getNrOfQualifiers());
            $this->updateRound($creator->getQualifyGroup()->getChildRound(), $creator->getNrOfQualifiers(), $newNrOfPoules);
        }
        $this->cleanupRemovedQualifyGroups($round, array_values($removedQualifyGroups->toArray()));
    }

    /**
     * @phpstan-param ArrayCollection<int|string, QualifyGroup>|PersistentCollection<int|string, QualifyGroup> $removedQualifyGroups
     * @psalm-param ArrayCollection<int|string, QualifyGroup> $removedQualifyGroups
     * @param Round $round
     * @param int $winnersOrLosers
     * @param int $newNrOfPlacesChildren
     * @return HorizontolPouleCreator
     */
    private function getHorizontalPouleCreator(
        ArrayCollection|PersistentCollection $removedQualifyGroups,
        Round $round,
        int $winnersOrLosers,
        int &$newNrOfPlacesChildren
        ) : HorizontolPouleCreator
    {
        /** @var QualifyGroup|false $qualifyGroup */
        $qualifyGroup = $removedQualifyGroups->first();
        $nrOfQualifiers = 0;
        if ($qualifyGroup === false) {
            $nextRoundNumber = $this->createNextRoundNumber($round);
            $qualifyGroup = new QualifyGroup($round, $winnersOrLosers, $nextRoundNumber);
            $nrOfQualifiers = $newNrOfPlacesChildren;
        } else {
            $removedQualifyGroups->removeElement($qualifyGroup);
            $round->addQualifyGroup($qualifyGroup);
            // warning: cannot make use of qualifygroup.horizontalpoules yet!

            // add and remove qualifiers
            $nrOfQualifiers = $qualifyGroup->getChildRound()->getNrOfPlaces();

            if ($nrOfQualifiers < $round->getPoules()->count() && $newNrOfPlacesChildren > $nrOfQualifiers) {
                $nrOfQualifiers = $round->getPoules()->count();
            }
            if ($nrOfQualifiers > $newNrOfPlacesChildren) {
                $nrOfQualifiers = $newNrOfPlacesChildren;
            } else {
                if ($nrOfQualifiers < $newNrOfPlacesChildren && $removedQualifyGroups->count() === 0) {
                    $nrOfQualifiers = $newNrOfPlacesChildren;
                }
            }
            if ($newNrOfPlacesChildren - $nrOfQualifiers === 1) {
                $nrOfQualifiers = $newNrOfPlacesChildren;
            }
        }
        return new HorizontolPouleCreator($qualifyGroup, $nrOfQualifiers);
    }

    // if roundnumber has no rounds left, also remove round number
    /**
     * @param Round $round
     * @param list<QualifyGroup> $removedQualifyGroups
     * @return void
     */
    protected function cleanupRemovedQualifyGroups(Round $round, array $removedQualifyGroups): void
    {
        $nextRoundNumber = $round->getNumber()->getNext();
        if ($nextRoundNumber === null) {
            return;
        }
        foreach ($removedQualifyGroups as $removedQualifyGroup) {
            foreach ($removedQualifyGroup->getHorizontalPoules() as $horizontalPoule) {
                $horizontalPoule->setQualifyGroup(null);
            }
            $nextRoundNumber->getRounds()->removeElement($removedQualifyGroup->getChildRound());
        }
        if ($nextRoundNumber->getRounds()->count() === 0) {
            $round->getNumber()->removeNext();
        }
    }

    public function calculateNewNrOfPoules(QualifyGroup $parentQualifyGroup, int $newNrOfPlaces): int
    {
        $oldNrOfPlaces = $parentQualifyGroup->getChildRound()->getNrOfPlaces();
        $oldNrOfPoules = $parentQualifyGroup->getChildRound()->getPoules()->count();

        if ($oldNrOfPoules === 0) {
            return 1;
        }
        if ($oldNrOfPlaces < $newNrOfPlaces) { // add
            if (($oldNrOfPlaces % $oldNrOfPoules) > 0 || ($oldNrOfPlaces / $oldNrOfPoules) === 2) {
                return $oldNrOfPoules;
            }
            return $oldNrOfPoules + 1;
        }
        // remove
        if (($newNrOfPlaces / $oldNrOfPoules) < 2) {
            return $oldNrOfPoules - 1;
        }
        return $oldNrOfPoules;
    }

    public function createNextRoundNumber(Round $round): RoundNumber
    {
        $nextRoundNumber = $round->getNumber()->getNext();
        if ($nextRoundNumber === null) {
            return $this->createRoundNumber($round);
        }
        return $nextRoundNumber;
    }

    public function createRoundNumber(Round $parentRound): RoundNumber
    {
        return $parentRound->getNumber()->createNext();
    }

    private function refillRound(Round $round, int $nrOfPlaces, int $nrOfPoules): ?Round
    {
        if ($nrOfPlaces <= 0) {
            return null;
        }

        if ((($nrOfPlaces / $nrOfPoules) < 2)) {
            throw new Exception('De minimale aantal deelnemers per poule is 2.', E_ERROR);
        }
        $round->getPoules()->clear();

        $pouleStructure = new BalancedPouleStructure($nrOfPlaces, $nrOfPoules);
        foreach ($pouleStructure->toArray() as $nrOfPlacesToAdd) {
            $poule = new Poule($round);
            for ($i = 0; $i < $nrOfPlacesToAdd; $i++) {
                new Place($poule);
            }
        }
        return $round;
    }

    protected function getRoot(Round $round): Round
    {
        $parent = $round->getParent();
        if ($parent === null) {
            return $round;
        }
        return $this->getRoot($parent);
    }

    protected function checkRanges(int $nrOfPlaces, int $nrOfPoules = null): void
    {
        if (count($this->placeRanges) === 0 || $nrOfPoules === null) {
            return;
        }

        foreach ($this->placeRanges as $placeRange) {
            if ($placeRange->isWithIn($nrOfPlaces)) {
                $pouleStructure = new BalancedPouleStructure($nrOfPlaces, $nrOfPoules);
                $flooredNrOfPlacesPerPoule = $pouleStructure->getRoundedNrOfPlacesPerPoule(true);
                if ($flooredNrOfPlacesPerPoule < $placeRange->getPlacesPerPouleRange()->getMin()) {
                    throw new Exception(
                        'er moeten minimaal ' . $placeRange->getPlacesPerPouleRange(
                        )->getMin() . ' deelnemers per poule zijn',
                        E_ERROR
                    );
                }
                $ceiledNrOfPlacesPerPoule = $pouleStructure->getRoundedNrOfPlacesPerPoule(false);
                if ($ceiledNrOfPlacesPerPoule > $placeRange->getPlacesPerPouleRange()->getMax()) {
                    throw new Exception(
                        'er mogen maximaal ' . $placeRange->getPlacesPerPouleRange(
                        )->getMax() . ' deelnemers per poule zijn',
                        E_ERROR
                    );
                }
                return;
            }
        }
        throw new Exception(
            'het aantal deelnemers is kleiner dan het minimum of groter dan het maximum',
            E_ERROR
        );
    }

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
}
