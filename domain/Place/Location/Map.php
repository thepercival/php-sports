<?php

namespace Sports\Place\Location;

use Sports\Place\Location as PlaceLocation;
use Sports\Competitor;

class Map
{
    private array $map;

    /**
     * @param array|Competitor[] $competitors
     */
    public function __construct(array $competitors)
    {
        $this->map = [];
        foreach( $competitors as $competitor ) {
            $this->map[$this->getPlaceLocationId($competitor) ] = $competitor;
        }
    }

    protected function getPlaceLocationId(PlaceLocation $placeLocation): string {
        return $placeLocation->getPouleNr() . '.' . $placeLocation->getPlaceNr();
    }

    public function getCompetitor(PlaceLocation $placeLocation): ?Competitor {
        if( array_key_exists( $this->getPlaceLocationId($placeLocation), $this->map) ) {
            return  $this->map[$this->getPlaceLocationId($placeLocation) ];
        }
        return null;
    }
}
