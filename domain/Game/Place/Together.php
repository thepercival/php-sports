<?php

namespace Sports\Game\Place;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Game\Together as TogetherGame;
use Sports\Score\Together as TogetherScore;
use Sports\Place as PlaceBase;
use Sports\Game\Place as GamePlaceBase;

class Together extends GamePlaceBase
{
    protected TogetherGame $game;
    protected int $gameRoundNumber;
    /**
     * @var ArrayCollection|TogetherScore[]
     */
    protected $scores;

    public function __construct(TogetherGame $game, PlaceBase $place, int $gameRoundNumber)
    {
        parent::__construct($place);
        $this->setGame($game);
        $this->gameRoundNumber = $gameRoundNumber;
        $this->scores = new ArrayCollection();
    }

    public function getPlace(): PlaceBase
    {
        return $this->place;
    }

    public function getGame(): TogetherGame
    {
        return $this->game;
    }

    protected function setGame(TogetherGame $game): void
    {
        if (!$game->getPlaces()->contains($this)) {
            $game->getPlaces()->add($this) ;
        }
        $this->game = $game;
    }

    public function getPlaceNr(): int
    {
        return $this->getPlace()->getNumber();
    }

    public function getGameRoundNumber(): int
    {
        return $this->gameRoundNumber;
    }

    /**
     * @return ArrayCollection|TogetherScore[]
     */
    public function getScores() {
        return $this->scores;
    }
}
