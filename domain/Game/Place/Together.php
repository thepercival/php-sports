<?php

namespace Sports\Game\Place;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Sports\Game\Together as TogetherGame;
use Sports\Score\Together as TogetherScore;
use Sports\Place as PlaceBase;
use Sports\Game\Place as GamePlaceBase;

class Together extends GamePlaceBase
{
    /**
     * @phpstan-var ArrayCollection<int|string, TogetherScore>|PersistentCollection<int|string, TogetherScore>
     * @psalm-var ArrayCollection<int|string, TogetherScore>
     */
    protected ArrayCollection|PersistentCollection $scores;

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
     * @phpstan-return ArrayCollection<int|string, TogetherScore>|PersistentCollection<int|string, TogetherScore>
     * @psalm-return ArrayCollection<int|string, TogetherScore>
     */
    public function getScores(): ArrayCollection|PersistentCollection {
        return $this->scores;
    }
}
