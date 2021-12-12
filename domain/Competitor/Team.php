<?php

declare(strict_types=1);

namespace Sports\Competitor;

use Sports\Competition;
use Sports\Competitor as CompetitorInterface;
use Sports\Place\Location as PlaceLocation;
use Sports\Place\LocationInterface;
use Sports\Team as TeamBase;
use SportsHelpers\Identifiable;

class Team extends Identifiable implements LocationInterface, CompetitorInterface
{
    use Base;

    public function __construct(
        protected Competition $competition,
        int $pouleNr,
        int $placeNr,
        protected TeamBase $team
    ) {
        if (!$competition->getTeamCompetitors()->contains($this)) {
            $competition->getTeamCompetitors()->add($this) ;
        }
        $this->setPouleNr($pouleNr);
        $this->setPlaceNr($placeNr);
    }

    public function getName(): string
    {
        return $this->team->getName();
    }

    public function getTeam(): TeamBase
    {
        return $this->team;
    }

    public function getCompetition(): Competition
    {
        return $this->competition;
    }
}
