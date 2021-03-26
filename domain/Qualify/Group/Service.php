<?php

namespace Sports\Qualify\Group;

use Sports\Structure\Service as StructureService;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Round;
use Sports\Qualify\Group as QualifyGroup;

class Service
{
    private StructureService $structureService;

    public function __construct(StructureService $structureService)
    {
        $this->structureService = $structureService;
    }

    public function splitFrom(HorizontalPoule $horizontalPoule): void
    {
        $qualifyGroup = $horizontalPoule->getQualifyGroup();
        if ($qualifyGroup === null) {
            return;
        }
        $nrOfPlacesChildRound = $qualifyGroup->getChildRound()->getNrOfPlaces();
        $horizontalPoules = $qualifyGroup->getHorizontalPoules();
        $idx = array_search($horizontalPoule, $horizontalPoules, true);
        if ($idx === false) {
            throw new \Exception('de horizontale poule kan niet gevonden worden', E_ERROR);
        }
        $splittedPoules = array_slice($horizontalPoules, $idx);
        $horizontalPoules = array_slice($horizontalPoules, 0, $idx);
        $round = $qualifyGroup->getRound();
        $newNrOfQualifiers = count($horizontalPoules) * $round->getPoules()->count();
        $newNrOfPoules = $this->structureService->calculateNewNrOfPoules($qualifyGroup, $newNrOfQualifiers);
        while (($newNrOfQualifiers / $newNrOfPoules) < 2) {
            $newNrOfPoules--;
        }
        $this->structureService->updateRound($qualifyGroup->getChildRound(), $newNrOfQualifiers, $newNrOfPoules);

        $nextRoundNumber = $this->structureService->createNextRoundNumber($round);
        $newQualifyGroup = new QualifyGroup($round, $qualifyGroup->getWinnersOrLosers(), $nextRoundNumber);
        $this->renumberQualifyGroups($round, $qualifyGroup->getWinnersOrLosers());

        $newChildRound = $newQualifyGroup->getChildRound();
        $splittedNrOfQualifiers = $nrOfPlacesChildRound - $newNrOfQualifiers;
        $splittedNrOfPoules = $this->structureService->calculateNewNrOfPoules($qualifyGroup, $newNrOfQualifiers);
        while (($splittedNrOfQualifiers / $splittedNrOfPoules) < 2) {
            $splittedNrOfPoules--;
        }
        $this->structureService->updateRound($newChildRound, $splittedNrOfQualifiers, $splittedNrOfPoules);

        foreach ($splittedPoules as $splittedPoule) {
            $splittedPoule->setQualifyGroup($newQualifyGroup);
        }
    }

    public function merge(QualifyGroup $firstQualifyGroup, QualifyGroup $secondQualifyGroup): void
    {
        $round = $firstQualifyGroup->getRound();
        $qualifyGroups = $round->getWinnersOrLosersQualifyGroups($firstQualifyGroup->getWinnersOrLosers());
        $index = $qualifyGroups->indexOf($secondQualifyGroup);
        $round->removeQualifyGroup($secondQualifyGroup);
        $this->renumberQualifyGroups($round, $firstQualifyGroup->getWinnersOrLosers());

        $horizontalPoules = $secondQualifyGroup->getHorizontalPoules();
        if ($index !== false) {
            unset($horizontalPoules[$index]);
        }

        $removedPoules = $secondQualifyGroup->getHorizontalPoules();
        foreach ($removedPoules as $removedPoule) {
            $removedPoule->setQualifyGroup($firstQualifyGroup);
        }
    }

//    public function getLosersReversed( ArrayCollection $qualifyGroups ) {
//
//        uasort( $qualifyGroups, function( QualifyGroup $qualifyGroupA, QualifyGroup $qualifyGroupB) {
//            if ($qualifyGroupA->getWinnersOrLosers() < $qualifyGroupB->getWinnersOrLosers()) {
//                return 1;
//            }
//            if ($qualifyGroupA->getWinnersOrLosers() > $qualifyGroupB->getWinnersOrLosers()) {
//                return -1;
//            }
//            if ( $qualifyGroupA->getNumber() < $qualifyGroupB->getNumber()) {
//                return ( $qualifyGroupA->getWinnersOrLosers() === QualifyGroup::WINNERS ) ? 1 : -1;
//            }
//            if ($qualifyGroupA->getNumber() > $qualifyGroupB->getNumber()) {
//                return ( $qualifyGroupA->getWinnersOrLosers() === QualifyGroup::WINNERS ) ? -1 : 1;
//            }
//            return 0;
//        });
//        return $qualifyGroups;
//    }

    protected function renumberQualifyGroups(Round $round, int $winnersOrLosers): void
    {
        $number = 1;
        foreach ($round->getWinnersOrLosersQualifyGroups($winnersOrLosers) as $qualifyGroup) {
            $qualifyGroup->setNumber($number++);
        }
    }
}
