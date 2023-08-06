<?php

namespace Sports\Competitor;

use Sports\Competitor;

class StartLocationMap
{
    /**
     * @var array<string, Competitor|null>
     */
    private array $map;

    /**
     * @param list<Competitor|StartLocation> $competitors
     */
    public function __construct(array $competitors)
    {
        $this->map = [];
        foreach ($competitors as $competitorOrLocation) {
            $value = $competitorOrLocation instanceof Competitor ? $competitorOrLocation : null;
            $this->map[$competitorOrLocation->getStartId()] = $value;
        }
    }

    public function getCompetitor(StartLocationInterface $startLocation): Competitor|null
    {
        if (array_key_exists($startLocation->getStartId(), $this->map)) {
            return $this->map[$startLocation->getStartId()];
        }
        return null;
    }
}
