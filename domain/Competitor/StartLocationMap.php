<?php

namespace Sports\Competitor;

use Sports\Competitor;

class StartLocationMap
{
    /**
     * @var array<string, Competitor>
     */
    private array $map;

    /**
     * @param list<Competitor> $competitors
     */
    public function __construct(array $competitors)
    {
        $this->map = [];
        foreach ($competitors as $competitor) {
            $this->map[$competitor->getStartId()] = $competitor;
        }
    }

    public function getCompetitor(StartLocationInterface $startLocation): ?Competitor
    {
        if (array_key_exists($startLocation->getStartId(), $this->map)) {
            return $this->map[$startLocation->getStartId()];
        }
        return null;
    }
}
