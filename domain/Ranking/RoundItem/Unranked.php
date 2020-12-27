<?php

namespace Sports\Ranking\RoundItem;

use Sports\Round;
use Sports\Place\Location as PlaceLocation;

class Unranked
{
    private Round $round;
    private PlaceLocation $placeLocation;
    /**
     * @var int
     */
    private $penaltyPoints;

    private int $games = 0;
    /**
     * @var float
     */
    private $points = 0;
    private int $scored = 0;
    private int $received = 0;
    private int $subScored = 0;
    private int $subReceived = 0;

    /**
     * @param Round $round
     * @param PlaceLocation $placeLocation
     * @param int|null $penaltyPoints
     */
    public function __construct(Round $round, PlaceLocation $placeLocation, ?int $penaltyPoints)
    {
        $this->round = $round;
        $this->placeLocation = $placeLocation;
        if ($penaltyPoints !== null) {
            $this->addPoints(-$penaltyPoints);
        }
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    public function getPlaceLocation(): PlaceLocation
    {
        return $this->placeLocation;
    }

    public function getGames(): int
    {
        return $this->games;
    }

    public function addGame()
    {
        $this->games++;
    }

    public function getPoints(): float
    {
        return $this->points;
    }

    public function addPoints(float $points)
    {
        $this->points += $points;
    }

    public function getScored(): int
    {
        return $this->scored;
    }

    public function addScored(int $scored)
    {
        $this->scored += $scored;
    }

    public function getReceived(): int
    {
        return $this->received;
    }

    public function addReceived(int $received)
    {
        $this->received += $received;
    }

    public function getDiff(): int
    {
        return $this->getScored() - $this->getReceived();
    }

    public function getSubScored(): int
    {
        return $this->subScored;
    }

    public function addSubScored(int $subScored)
    {
        $this->subScored += $subScored;
    }

    public function getSubReceived(): int
    {
        return $this->subReceived;
    }

    public function addSubReceived(int $subReceived)
    {
        $this->subReceived += $subReceived;
    }

    public function getSubDiff(): int
    {
        return $this->getSubScored() - $this->getSubReceived();
    }
}
