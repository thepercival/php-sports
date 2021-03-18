<?php

namespace Sports\Competitor;

use Sports\Competition;
use Sports\Competitor as CompetitorInterface;
use Sports\Place\Location as PlaceLocation;
use Sports\Team as TeamBase;
use SportsHelpers\Identifiable;

class Team extends Identifiable implements PlaceLocation, CompetitorInterface
{
    /**
     * @var TeamBase
     */
    protected $team;
    /**
     * @var Competition
     */
    protected $competition;

    use Base;

    public function __construct(Competition $competition, int $pouleNr, int $placeNr, TeamBase $team)
    {
        $this->setTeam($team);
        $this->setCompetition($competition);
        $this->setPouleNr( $pouleNr );
        $this->setPlaceNr( $placeNr );
    }

    public function getName(): string
    {
        return $this->team->getName();
    }

    public function getTeam(): TeamBase
    {
        return $this->team;
    }

    public function setTeam(TeamBase $team): void
    {
        $this->team = $team;
    }

    public function getCompetition(): Competition
    {
        return $this->competition;
    }

    public function setCompetition(Competition $competition): void
    {
        if ($this->competition === null and !$competition->getTeamCompetitors()->contains($this)) {
            $competition->getTeamCompetitors()->add($this) ;
        }
        $this->competition = $competition;
    }
}
