<?php

declare(strict_types=1);

namespace Sports\Planning\GameAmountConfig;

use Sports\Planning\Config as PlanningConfig;
use Sports\Planning\GameAmountConfig;
use Sports\Round\Number as RoundNumber;
use Sports\Competition\Sport as CompetitionSport;

class Service
{
    public function getDefaultAmount(CompetitionSport $competitionSport): int
    {
        $nrOfGamePlaces = $competitionSport->getNrOfHomePlaces() + $competitionSport->getNrOfAwayPlaces();
        return $nrOfGamePlaces > 2 ? 0 : PlanningConfig::DEFAULTGAMEAMOUNT;
    }

    public function getDefaultNrOfGamesPerPlace(CompetitionSport $competitionSport): int
    {
        $nrOfGamePlaces = $competitionSport->getNrOfHomePlaces() + $competitionSport->getNrOfAwayPlaces();
        return $nrOfGamePlaces > 2 ? PlanningConfig::DEFAULTGAMEAMOUNT : 0;
    }

    public function create(CompetitionSport $competitionSport, RoundNumber $roundNumber): GameAmountConfig
    {
        return new GameAmountConfig(
            $competitionSport,
            $roundNumber,
            $this->getDefaultAmount($competitionSport),
            $this->getDefaultNrOfGamesPerPlace($competitionSport)
        );
    }
}
