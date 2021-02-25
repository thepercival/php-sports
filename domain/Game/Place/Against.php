<?php

namespace Sports\Game\Place;

use Sports\Game\Against as AgainstGame;
use Sports\Place as PlaceBase;
use Sports\Game\Place as GamePlaceBase;

class Against extends GamePlaceBase
{
    private AgainstGame $game;
    private int $side;

    public function __construct(AgainstGame $game, PlaceBase $place, int $side )
    {
        parent::__construct($place);
        $this->setGame($game);
        $this->side = $side;
    }

    public function getGame(): AgainstGame
    {
        return $this->game;
    }

    protected function setGame(AgainstGame $game)
    {
        if (!$game->getPlaces()->contains($this)) {
            $game->getPlaces()->add($this) ;
        }
        $this->game = $game;
    }

    public function getSide(): int
    {
        return $this->side;
    }
}
