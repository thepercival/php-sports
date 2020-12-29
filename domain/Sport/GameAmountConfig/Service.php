<?php

namespace Sports\Sport\GameAmountConfig;

use Sports\Planning\Config as PlanningConfig;
use Sports\Sport;
use Sports\Sport\GameAmountConfig as SportGameAmountConfig;
use Sports\Round\Number as RoundNumber;
use Sports\Competition\Sport as CompetitionSport;
use SportsHelpers\SportConfig;

class Service
{
    public function createDefault(CompetitionSport $competitionSport, RoundNumber $roundNumber)
    {
        $gameMode = $roundNumber->getValidPlanningConfig()->getGameMode();
        $amount = PlanningConfig::DEFAULTGAMEAMOUNT;
        if( $gameMode === SportConfig::GAMEMODE_TOGETHER ) {
            $amount = $competitionSport->getFields()->count();
        }
        return new SportGameAmountConfig($competitionSport, $roundNumber, $amount );
    }

    public function copy(CompetitionSport $competitionSport, RoundNumber $roundNumber, int $amount)
    {
        return new SportGameAmountConfig($competitionSport, $roundNumber, $amount);
    }
}
