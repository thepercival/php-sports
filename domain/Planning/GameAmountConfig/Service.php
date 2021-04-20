<?php

namespace Sports\Planning\GameAmountConfig;

use Sports\Planning\Config as PlanningConfig;
use Sports\Planning\GameAmountConfig;
use Sports\Round\Number as RoundNumber;
use Sports\Competition\Sport as CompetitionSport;
use SportsHelpers\GameMode;

class Service
{
    public function createDefault(CompetitionSport $competitionSport, RoundNumber $roundNumber): GameAmountConfig
    {
        $amount = PlanningConfig::DEFAULTGAMEAMOUNT;
//        if ($competitionSport->getGameMode() === GameMode::TOGETHER) {
//            $amount = $competitionSport->getFields()->count();
//        }
        return new GameAmountConfig($competitionSport, $roundNumber, $amount);
    }

    public function copy(CompetitionSport $competitionSport, RoundNumber $roundNumber, int $amount): GameAmountConfig
    {
        return new GameAmountConfig($competitionSport, $roundNumber, $amount);
    }
}
