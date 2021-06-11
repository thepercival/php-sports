<?php
declare(strict_types=1);

namespace Sports\Game;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Game as GameBase;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Place;
use Sports\Poule;

class Together extends GameBase
{
    /**
     * @phpstan-var ArrayCollection<int|string, TogetherGamePlace>|PersistentCollection<int|string, TogetherGamePlace>
     * @psalm-var ArrayCollection<int|string, TogetherGamePlace>
     */
    protected ArrayCollection|PersistentCollection $places;

    public function __construct(
        Poule $poule,
        int $batchNr,
        DateTimeImmutable $startDateTime,
        CompetitionSport $competitionSport
    )
    {
        parent::__construct($poule, $batchNr, $startDateTime, $competitionSport);
        $this->places = new ArrayCollection();
        if (!$poule->getTogetherGames()->contains($this)) {
            $poule->getTogetherGames()->add($this);
        }
    }

    /**
     * @phpstan-return ArrayCollection<int|string, TogetherGamePlace>|PersistentCollection<int|string, TogetherGamePlace>
     * @psalm-return ArrayCollection<int|string, TogetherGamePlace>
     */
    public function getPlaces(): ArrayCollection|PersistentCollection
    {
        return $this->places;
    }

    public function isParticipating(Place $place): bool
    {
        $places = $this->getPlaces()->map(function (TogetherGamePlace $gamePlace): Place {
            return $gamePlace->getPlace();
        });
        return $places->contains($place);
    }
}
