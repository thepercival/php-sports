<?php

declare(strict_types=1);

namespace Sports\Game;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Competition\CompetitionSport as CompetitionSport;
use Sports\Game as GameBase;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Place;
use Sports\Poule;

class Together extends GameBase
{
    /**
     * @var Collection<int|string, TogetherGamePlace>
     */
    protected Collection $places;

    public function __construct(
        Poule $poule,
        int $batchNr,
        DateTimeImmutable $startDateTime,
        CompetitionSport $competitionSport
    ) {
        parent::__construct($poule, $batchNr, $startDateTime, $competitionSport);
        $this->places = new ArrayCollection();
        if (!$poule->getTogetherGames()->contains($this)) {
            $poule->getTogetherGames()->add($this);
        }
    }

    /**
     * @return Collection<int|string, TogetherGamePlace>
     */
    public function getPlaces(): Collection
    {
        return $this->places;
    }

    public function isParticipating(Place $place): bool
    {
        foreach( $this->getPlaces() as $gamePlace) {
            if( $gamePlace->getPlace() === $place) {
                return true;
            }
        }
        return false;
    }
}
