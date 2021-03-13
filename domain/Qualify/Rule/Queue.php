<?php

namespace Sports\Qualify\Rule;

use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;

class Queue
{
    const START = 1;
    const END = 2;

    /**
     * @var array<SingleQualifyRule|MultipleQualifyRule>
     */
    private array $qualifyRules;

    public function __construct()
    {
        $this->qualifyRules = [];
    }

    public function add(int $startEnd, SingleQualifyRule|MultipleQualifyRule $qualifyRule)
    {
        if ($startEnd === Queue::START) {
            $this->qualifyRules[] = $qualifyRule;
        } else {
            array_unshift($this->qualifyRules, $qualifyRule);
        }
    }

    public function remove(int $startEnd)
    {
        return $startEnd === Queue::START ? array_shift($this->qualifyRules) : array_pop($this->qualifyRules);
    }

    public function isEmpty(): bool
    {
        return count($this->qualifyRules) === 0;
    }

    public function toggle(int $startEnd): int
    {
        return $startEnd === Queue::START ? Queue::END : Queue::START;
    }

    /**
     * bij 5 poules, haal 2 na laatste naar achterste plek
     *
     * @param int $nrOfPoules
     */
    public function shuffleIfUnevenAndNoMultiple(int $nrOfPoules)
    {
        if (($nrOfPoules % 2) === 0 || $nrOfPoules < 3) {
            return;
        }

        if (count($this->qualifyRules) > 0) {
            $lastItem = $this->qualifyRules[count($this->qualifyRules)-1];
            if ($lastItem instanceof MultipleQualifyRule) {
                return;
            }
        }
        $index = (count($this->qualifyRules) - 1) - ((($nrOfPoules + 1) / 2) - 1);
        $x = array_splice($this->qualifyRules, (int)$index, 1);
        $this->qualifyRules[] = array_pop($x);
    }
}
