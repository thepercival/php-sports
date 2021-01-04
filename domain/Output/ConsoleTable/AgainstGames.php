<?php

namespace Sports\Output\ConsoleTable;

use DateTime;
use LucidFrame\Console\ConsoleTable;
use Sports\Competition;
use Sports\Game\Against as AgainstGame;
use Sports\Score\Against as AgainstScore;
use Sports\NameService;
use Sports\Place\Location\Map as PlaceLocationMap;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\State;

class AgainstGames
{
    /**
     * @param Competition $competition
     * @param array|AgainstGame[] $games
     * @param array|TeamCompetitor[] $teamCompetitors
     */
    public function display( Competition $competition, array $games, array $teamCompetitors ) {
        $table = new ConsoleTable();
        $table->setHeaders(array('league', 'season', 'batchNr', 'id', 'datetime', 'state', 'home', 'score', 'away' ) );

        $nameService = new NameService( new PlaceLocationMap( $teamCompetitors ) );

        foreach( $games as $game ) {
            $row = array(
                $competition->getLeague()->getName(),
                $competition->getSeason()->getName(),
                $game->getBatchNr(),
                $game->getId(),
                $game->getStartDateTime()->format(DateTime::ATOM),
                (new State($game->getState()))->getDescription(),
                $nameService->getPlacesFromName($game->getPlaces(AgainstGame::HOME), true, true),
                $this->getScore($game),
                $nameService->getPlacesFromName($game->getPlaces(AgainstGame::AWAY), true, true),
            );
            $table->addRow($row);
        }
        $table->display();
    }

    protected function getScore(AgainstGame $game): string {
        return join( "&", $game->getScores()->map( function( AgainstScore $gameScore ): string {
            return $gameScore->getHomeScore() . " - " . $gameScore->getAwayScore() ;
        })->toArray() );
    }
}