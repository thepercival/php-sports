<?php

namespace Sports\Competitor;

use Sports\Place\Location as PlaceLocation;
use Sports\Competitor;

class Map
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
            $this->map[$competitor->getRoundLocationId()] = $competitor;
        }
    }

    public function getCompetitor(PlaceLocation $placeLocation): ?Competitor
    {
        if (array_key_exists($placeLocation->getRoundLocationId(), $this->map)) {
            return  $this->map[$placeLocation->getRoundLocationId()];
        }
        return null;
    }
}
