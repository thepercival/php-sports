<?php
declare(strict_types=1);

namespace Sports\Game\Place;

use Sports\Game\Against as AgainstGame;
use Sports\Place as PlaceBase;
use Sports\Game\Place as GamePlaceBase;

class Against extends GamePlaceBase
{
    public function __construct(private AgainstGame $game, PlaceBase $place, private int $side)
    {
        parent::__construct($place);
        if (!$game->getPlaces()->contains($this)) {
            $game->getPlaces()->add($this) ;
        }
    }

    public function getGame(): AgainstGame
    {
        return $this->game;
    }

    public function getSide(): int
    {
        return $this->side;
    }
}
