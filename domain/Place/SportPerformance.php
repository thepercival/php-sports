<?php

namespace Sports\Place;

use Sports\Place;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Place\Location as PlaceLocation;

class SportPerformance
{
    private int $penaltyPoints = 0;
    private int $games = 0;
    /**
     * @var float
     */
    private $points = 0;
    private int $scored = 0;
    private int $received = 0;
    private int $subScored = 0;
    private int $subReceived = 0;

    public function __construct(private $competitionSport, private Place $place, ?int $penaltyPoints)
    {
        if ($penaltyPoints !== null) {
            $this->addPoints(-$penaltyPoints);
        }
    }

    public function getCompetitionSport(): CompetitionSport
    {
        return $this->competitionSport;
    }

    public function getPlace(): Place
    {
        return $this->place;
    }

    public function getRoundLocationId(): string
    {
        return $this->place->getRoundLocationId();
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
