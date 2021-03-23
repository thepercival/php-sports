<?php
declare(strict_types=1);

namespace Sports;

use Closure;
use Sports\Round\Number as RoundNumber;
use Sports\Qualify\Group as QualifyGroup;

class Structure
{
    public function __construct(protected RoundNumber $firstRoundNumber, protected Round $rootRound)
    {
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
            /** @var Closure(RoundNumber):RoundNumber $getLastRoundNumber */
            $next = $roundNumber->getNext();
            if ($next === null) {
                return $roundNumber;
            }
            return $getLastRoundNumber($next);
        };
        return $getLastRoundNumber($this->getFirstRoundNumber());
    }

//    public function getLastRoundNumber(): RoundNumber
//    {
//        $first = $this->getFirstRoundNumber();
//        while ($second = $first->getNext()) {
//            $first = $second;
//        }
//        return $first;
//    }

    /**
     * @return list<RoundNumber>
     */
    public function getRoundNumbers(): array
    {
        $roundNumbers = [];
        $roundNumber = $this->getFirstRoundNumber();
        while ($roundNumber !== null) {
            array_push($roundNumbers, $roundNumber);
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

    public function setStructureNumbers(): void
    {
        $nrOfDropoutPlaces = 0;
        $setRoundStructureNumbers = function (Round $round) use (&$setRoundStructureNumbers, &$nrOfDropoutPlaces): void {
            foreach ($round->getQualifyGroups(QualifyGroup::WINNERS) as $qualifyGroup) {
                /** @var Closure(Round): void $setRoundStructureNumbers */
                $setRoundStructureNumbers($qualifyGroup->getChildRound());
            }
            /** @var int $nrOfDropoutPlaces */
            $round->setStructureNumber($nrOfDropoutPlaces);
            $nrOfDropoutPlaces += $round->getNrOfDropoutPlaces();
            $losersQualifyGroups = array_reverse($round->getQualifyGroups(QualifyGroup::LOSERS)->slice(0));
            foreach ($losersQualifyGroups as $qualifyGroup) {
                /** @var Closure(Round): void $setRoundStructureNumbers */
                $setRoundStructureNumbers($qualifyGroup->getChildRound());
            }
        };

        $pouleNr = 1;
        $setPouleStructureNumbers = function (RoundNumber $roundNumber) use (&$setPouleStructureNumbers, &$pouleNr): void {
            /** @var Closure(RoundNumber): void $setPouleStructureNumbers */
            $rounds = array_values($roundNumber->getRounds()->toArray());
            usort($rounds, function (Round $roundA, Round $roundB) {
                return ($roundA->getStructureNumber() > $roundB->getStructureNumber()) ? 1 : -1;
            });
            foreach ($rounds as $round) {
                foreach ($round->getPoules() as $poule) {
                    /** @var int $pouleNr */
                    $poule->setStructureNumber($pouleNr++);
                }
            }
            $nextRoundNumber = $roundNumber->getNext();
            if ($nextRoundNumber !== null) {
                $setPouleStructureNumbers($nextRoundNumber);
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
