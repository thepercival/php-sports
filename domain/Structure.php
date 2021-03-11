<?php

namespace Sports;

use Sports\Round\Number as RoundNumber;
use Sports\Qualify\Group as QualifyGroup;

class Structure
{
    /**
     * @var RoundNumber
     */
    protected $firstRoundNumber;
    /**
     * @var Round
     */
    protected $rootRound;

    public function __construct(RoundNumber $firstRoundNumber, Round $rootRound)
    {
        $this->firstRoundNumber = $firstRoundNumber;
        $this->rootRound = $rootRound;
    }

    public function getFirstRoundNumber(): RoundNumber
    {
        return $this->firstRoundNumber;
    }

    public function getRootRound(): Round
    {
        return $this->rootRound;
    }

    public function getLastRoundNumber(): RoundNumber
    {
        $getLastRoundNumber = function (RoundNumber $roundNumber) use (&$getLastRoundNumber) : RoundNumber {
            if (!$roundNumber->hasNext()) {
                return $roundNumber;
            }
            return $getLastRoundNumber($roundNumber->getNext());
        };
        return $getLastRoundNumber($this->getFirstRoundNumber());
    }

    /**
     * @return array|RoundNumber[]
     */
    public function getRoundNumbers(): array
    {
        $roundNumbers = [];
        $roundNumber = $this->getFirstRoundNumber();
        while ($roundNumber !== null) {
            $roundNumbers[] = $roundNumber;
            $roundNumber = $roundNumber->getNext();
        }
        return $roundNumbers;
    }


    public function getRoundNumber(int $roundNumberAsValue): ?RoundNumber
    {
        $roundNumber = $this->getFirstRoundNumber();
        while ($roundNumber !== null) {
            if ($roundNumber->getNumber() === $roundNumberAsValue) {
                return $roundNumber;
            }
            $roundNumber = $roundNumber->getNext();
        }
        return $roundNumber;
    }

    public function setStructureNumbers()
    {
        $nrOfDropoutPlaces = 0;
        $setRoundStructureNumbers = function (Round $round) use (&$setRoundStructureNumbers, &$nrOfDropoutPlaces): void {
            foreach ($round->getQualifyGroups(QualifyGroup::WINNERS) as $qualifyGroup) {
                $setRoundStructureNumbers($qualifyGroup->getChildRound());
            }
            $round->setStructureNumber($nrOfDropoutPlaces);
            $nrOfDropoutPlaces += $round->getNrOfDropoutPlaces();
            $losersQualifyGroups = array_reverse($round->getQualifyGroups(QualifyGroup::LOSERS)->slice(0));
            foreach ($losersQualifyGroups as $qualifyGroup) {
                $setRoundStructureNumbers($qualifyGroup->getChildRound());
            }
        };

        $pouleNr = 1;
        $setPouleStructureNumbers = function (RoundNumber $roundNumber) use (&$setPouleStructureNumbers, &$pouleNr): void {
            $rounds = $roundNumber->getRounds()->toArray();
            uasort($rounds, function (Round $roundA, Round $roundB) {
                return ($roundA->getStructureNumber() > $roundB->getStructureNumber()) ? 1 : -1;
            });
            foreach ($rounds as $round) {
                /** @var Poule $poule */
                foreach ($round->getPoules() as $poule) {
                    $poule->setStructureNumber($pouleNr++);
                }
            }
            if ($roundNumber->hasNext()) {
                $setPouleStructureNumbers($roundNumber->getNext());
            }
        };

        $setRoundStructureNumbers($this->rootRound);
        $setPouleStructureNumbers($this->firstRoundNumber);
    }


//
//    public function getRound( array $winnersOrLosersPath ): Round {
//        $round = $this->getRootRound();
//        foreach( $winnersOrLosersPath as $winnersOrLosers ) {
//            $round = $round->getChildRoundDep($winnersOrLosers);
//        }
//        return $round;
//    }
//
//    public function getRoundNumberById(int $id): ?RoundNumber {
//        $roundNumber = $this->getFirstRoundNumber();
//        while( $roundNumber !== null ) {
//            if($roundNumber->getId() === $id) {
//                return $roundNumber;
//            }
//            $roundNumber = $roundNumber->getNext();
//        }
//        return $roundNumber;
//    }
//
//    public function setQualifyRules() {
//        if( count( $this->getRootRound()->getToQualifyRules() ) === 0 ) {
//            $this->setQualifyRulesHelper( $this->getRootRound() );
//        }
//    }
//
//    protected function setQualifyRulesHelper( Round $parentRound )
//    {
//        throw new \Exception("setQualifyRulesHelper", E_ERROR);
////        foreach ($parentRound->getChildRounds() as $childRound) {
////            $qualifyService = new QualifyService($childRound);
////            $qualifyService->createRules();
////            $this->setQualifyRulesHelper( $childRound );
////        }
//    }
}
