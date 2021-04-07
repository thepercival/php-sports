<?php
declare(strict_types=1);

namespace Sports;

use Closure;
use Sports\Round\Number as RoundNumber;

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
}
