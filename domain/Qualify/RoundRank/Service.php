<?php

declare(strict_types=1);

namespace Sports\Qualify\RoundRank;

use Sports\Category;
use Sports\Round;

class Service
{
    /**
     * @var array<int, Calculator>
     */
    protected array $map = [];

    public function __construct()
    {
    }

    public function getRank(Round $round): int
    {
        $roundRankCalculator = $this->getRoundRankCalculator($round->getCategory());
        return $roundRankCalculator->getRank($round);
    }

    private function getRoundRankCalculator(Category $category): Calculator
    {
        if (!isset($this->map[$category->getNumber()])) {
            $this->map[$category->getNumber()] = new Calculator($category);
        }
        return $this->map[$category->getNumber()];
    }
}
