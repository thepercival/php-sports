<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 13:19
 */

namespace Sports\Competitor;

use Sports\Competition;
use Sports\Competitor as CompetitorInterface;
use Sports\Place\Location as PlaceLocation;
use Sports\Team as TeamBase;

class Team implements PlaceLocation, CompetitorInterface
{
    /**
     * @var int|string
     */
    protected $id;
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

    public function setTeam(TeamBase $team)
    {
        $this->team = $team;
    }

    public function getCompetition(): Competition
    {
        return $this->competition;
    }

    public function setCompetition(Competition $competition)
    {
        $this->competition = $competition;
    }
}
