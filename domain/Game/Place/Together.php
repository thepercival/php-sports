<?php

namespace Sports\Game\Place;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Game\Together as TogetherGame;
use Sports\Score\Together as TogetherScore;
use Sports\Place as PlaceBase;
use Sports\Game\Place as GamePlaceBase;

class Together extends GamePlaceBase
{
    /**
     * @var ArrayCollection<int|string, TogetherScore>
     */
    protected ArrayCollection $scores;

    public function __construct(protected TogetherGame $game, PlaceBase $place, protected int $gameRoundNumber)
    {
        parent::__construct($place);
        if (!$game->getPlaces()->contains($this)) {
            $game->getPlaces()->add($this) ;
        }
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

    public function getPlaceNr(): int
    {
        return $this->getPlace()->getNumber();
    }

    public function getGameRoundNumber(): int
    {
        return $this->gameRoundNumber;
    }

    /**
     * @return ArrayCollection<int|string, TogetherScore>
     */
    public function getScores(): ArrayCollection {
        return $this->scores;
    }
}
