<?php

namespace Sports\Game;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Game as GameBase;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Place;
use Sports\Poule;

class Together extends GameBase
{
    /**
     * @var TogetherGamePlace[] | Collection
     */
    protected $places;

    public function __construct(Poule $poule, int $batchNr, DateTimeImmutable $startDateTime, CompetitionSport $competitionSport)
    {
        parent::__construct($poule, $batchNr, $startDateTime, $competitionSport);
        $this->places = new ArrayCollection();
    }

    /**
     * @return Collection | TogetherGamePlace[]
     */
    public function getPlaces(): Collection
    {
        return $this->places;
    }

    /**
     * @param Place $place
     * @return bool
     */
    public function isParticipating(Place $place): bool
    {
        $places = $this->getPlaces()->map(function ($gamePlace) {
            return $gamePlace->getPlace();
        });
        return $places->contains($place);
    }

}
