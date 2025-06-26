<?php

namespace Sports\Team;

use Sports\Competition;
use Sports\Competitor\StartLocationMap;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Team;
use SportsHelpers\Against\AgainstSide;
use Sports\Game\Against as AgainstGame;

final class Calculator
{
    protected StartLocationMap $startLocationMap;

    public function __construct(Competition $competition)
    {
        $competitors = array_values($competition->getTeamCompetitors()->toArray());
        $this->startLocationMap = new StartLocationMap($competitors);
    }

    /**
     * @param AgainstGame $againstGame
     * @return list<Team>
     */
    public function getTeams(AgainstGame $againstGame): array {
        $teams = [];
        foreach ([AgainstSide::Home, AgainstSide::Away] as $side) {
            try {
                $teams[] = $this->getSingleTeam($againstGame, $side);
            } catch( \Exception $e ) {

            }
        }
        return $teams;
    }

    public function getSingleTeam(AgainstGame $againstGame, AgainstSide $side): Team
    {
        foreach ($againstGame->getSidePlaces($side) as $gamePlace) {
            $startLocation = $gamePlace->getPlace()->getStartLocation();
            if ($startLocation === null) {
                throw new \Exception('startlocation could not be found', E_ERROR);
            }
            /** @var TeamCompetitor|null $teamCompetitor */
            $teamCompetitor = $this->startLocationMap->getCompetitor($startLocation);
            if ($teamCompetitor === null) {
                throw new \Exception('team could not be found', E_ERROR);
            }
            return $teamCompetitor->getTeam();


        }
        throw new \Exception('team not found', E_ERROR);
    }
//
//foreach ($gamePlace->getParticipations() as $gameParticipation) {
//$teamPlayer = $gameParticipation->getPlayer();
//if ($teamPlayer->getTeam() !== $teamCompetitor->getTeam()) {
//$message = 'teams of player and gameparticipation are not equal';
//$this->getLogger()->error($message);
//continue;
//}
//
//if (!$teamPlayer->getPeriod()->contains($game->getStartDateTime())) {
//    $message = 'game is outside playerperiod ' . $teamPlayerOutput->getString(
//            $teamPlayer,
//            ''
//        );
//    $this->getLogger()->error($message);
//    continue;
//}
//if (!$seasonPeriod->contains($teamPlayer->getPeriod())) {
//    $message = 'player-period ' . $teamPlayerOutput->getString(
//            $teamPlayer,
//            ''
//        ) . ' is outside season ' . $seasonPeriod->toIso80000('Y-m-d');
//    $this->getLogger()->error($message);
//}
//}
}