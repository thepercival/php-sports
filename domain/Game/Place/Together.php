<?php

namespace Sports\Game\Place;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Game\Together as TogetherGame;
use Sports\Place as PlaceBase;
use Sports\Game\Place as GamePlaceBase;

class Together extends GamePlaceBase
{
    protected TogetherGame $game;
    protected int $gameRoundNumber;
    /**
     * @var int|null
     */
    protected $score;

    public function __construct(TogetherGame $game, PlaceBase $place, int $gameRoundNumber)
    {
        parent::__construct($place);
        $this->setGame($game);
        $this->gameRoundNumber = $gameRoundNumber;
    }

    public function getPlace(): PlaceBase
    {
        return $this->place;
    }

    public function getGame(): TogetherGame
    {
        return $this->game;
    }

    protected function setGame(TogetherGame $game)
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

    public function setScore( int $score ) {
        $this->score = $score;
    }

    public function getScore(): ?int {
        return $this->score;
    }
}
