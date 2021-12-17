<?php

declare(strict_types=1);

namespace Sports\Competitor;

use Sports\Competition;
use Sports\Competitor as CompetitorInterface;
use Sports\Place\LocationInterface;
use Sports\Team as TeamBase;

class Team extends Base implements LocationInterface, CompetitorInterface
{
    public function __construct(
        protected Competition $competition,
        int $pouleNr,
        int $placeNr,
        protected TeamBase $team
    ) {
        parent::__construct($pouleNr, $placeNr);
        if (!$competition->getTeamCompetitors()->contains($this)) {
            $competition->getTeamCompetitors()->add($this);
        }
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
