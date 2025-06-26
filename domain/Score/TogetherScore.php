<?php

namespace Sports\Score;

use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Score;

final class TogetherScore extends Score
{
    protected TogetherGamePlace $gamePlace;
    protected int $score;

    public function __construct(TogetherGamePlace $gamePlace, int $score, int $phase, int $number = null)
    {
        $this->score = $score;
        $this->setGamePlace($gamePlace);
        if ($number === null) {
            $number = $gamePlace->getScores()->count();
        }
        parent::__construct($phase, $number);
    }

    public function getGamePlace(): TogetherGamePlace
    {
        return $this->gamePlace;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    protected function setGamePlace(TogetherGamePlace $gamePlace): void
    {
        if (!$gamePlace->getScores()->contains($this)) {
            $gamePlace->getScores()->add($this) ;
        }
        $this->gamePlace = $gamePlace;
    }
}
