<?php

declare(strict_types=1);

namespace Sports\Place;

use Sports\Place;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Place\Location as PlaceLocation;

class Performance
{
    private int $games = 0;
    private float $points = 0.0;
    private float $scored = 0;
    private float $received = 0;
    private float $subScored = 0;
    private float $subReceived = 0;

    public function __construct(private Place $place)
    {
    }

    public function getPlace(): Place
    {
        return $this->place;
    }

    public function getPlaceLocation(): PlaceLocation
    {
        return $this->place;
    }

    public function getGames(): int
    {
        return $this->games;
    }

    public function addGame(): void
    {
        $this->games++;
    }

    public function getPoints(): float
    {
        return $this->points;
    }

    public function addPoints(float $points): void
    {
        $this->points += $points;
    }

    public function getScored(): float
    {
        return $this->scored;
    }

    public function addScored(float $scored): void
    {
        $this->scored += $scored;
    }

    public function getReceived(): float
    {
        return $this->received;
    }

    public function addReceived(float $received): void
    {
        $this->received += $received;
    }

    public function getDiff(): float
    {
        return $this->getScored() - $this->getReceived();
    }

    public function getSubScored(): float
    {
        return $this->subScored;
    }

    public function addSubScored(float $subScored): void
    {
        $this->subScored += $subScored;
    }

    public function getSubReceived(): float
    {
        return $this->subReceived;
    }

    public function addSubReceived(float $subReceived): void
    {
        $this->subReceived += $subReceived;
    }

    public function getSubDiff(): float
    {
        return $this->getSubScored() - $this->getSubReceived();
    }

    public function addSportPerformace(SportPerformance $sportPerformance): void
    {
        $this->addGame();
        $this->addPoints($sportPerformance->getPoints());
        $this->addScored($sportPerformance->getScored());
        $this->addReceived($sportPerformance->getReceived());
        $this->addSubScored($sportPerformance->getSubScored());
        $this->addSubReceived($sportPerformance->getSubReceived());
    }
}
