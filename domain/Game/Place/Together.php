<?php
declare(strict_types=1);

namespace Sports\Game\Place;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Game\Place as GamePlaceBase;
use Sports\Game\Together as TogetherGame;
use Sports\Place as PlaceBase;
use Sports\Score\Together as TogetherScore;

class Together extends GamePlaceBase
{
    /**
     * @var Collection<int|string, TogetherScore>
     */
    protected Collection $scores;

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
        return $this->getPlace()->getPlaceNr();
    }

    public function getGameRoundNumber(): int
    {
        return $this->gameRoundNumber;
    }

    /**
     * @return Collection<int|string, TogetherScore>
     */
    public function getScores(): Collection
    {
        return $this->scores;
    }
}
