<?php
declare(strict_types=1);

namespace Sports\Qualify\Rule;

use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;

class Queue
{
    const START = 1;
    const END = 2;

    /**
     * @var list<SingleQualifyRule|MultipleQualifyRule>
     */
    private array $qualifyRules;

    public function __construct()
    {
        $this->qualifyRules = [];
    }

    public function add(int $startEnd, SingleQualifyRule|MultipleQualifyRule $qualifyRule): void
    {
        if ($startEnd === Queue::START) {
            array_push($this->qualifyRules, $qualifyRule);
        } else {
            array_unshift($this->qualifyRules, $qualifyRule);
        }
    }

    public function remove(int $startEnd): MultipleQualifyRule|SingleQualifyRule|null
    {
        return $startEnd === Queue::START ? array_shift($this->qualifyRules) : array_pop($this->qualifyRules);
    }

    public function isEmpty(): bool
    {
        return count($this->qualifyRules) === 0;
    }

    public function getOpposite(int $startEnd): int
    {
        return $startEnd === Queue::START ? Queue::END : Queue::START;
    }

    // bij 5 poules, haal 2 na laatste naar achterste plek
    public function moveCenterSingleRuleBack(int $nrOfPoules): void
    {
        if (($nrOfPoules % 2) === 0 || $nrOfPoules < 3) {
            return;
        }
        $lastRuleTmp = end($this->qualifyRules);
        if ($lastRuleTmp instanceof MultipleQualifyRule) {
            return;
        }
        $index = (count($this->qualifyRules) - 1) - ((($nrOfPoules + 1) / 2) - 1);
        /** @var list<SingleQualifyRule|MultipleQualifyRule> $removedItems */
        $removedItems = array_splice($this->qualifyRules, (int)$index, 1);
        $lastItem2 = end($removedItems);
        if ($lastItem2 instanceof MultipleQualifyRule || $lastItem2 instanceof SingleQualifyRule) {
            $this->qualifyRules[] = $lastItem2;
        }
    }
}
