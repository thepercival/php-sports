<?php

namespace Sports\Output\ConsoleTable;

use LucidFrame\Console\ConsoleTable;
use Sports\Competition;
use Sports\Game;
use Sports\NameService;
use Sports\Place\Location\Map as PlaceLocationMap;
use Sports\Competitor\Team as TeamCompetitor;

class Games
{
    /**
     * @param Competition $competition
     * @param array|Game[] $games
     * @param array|TeamCompetitor[] $teamCompetitors
     */
    public function display( Competition $competition, array $games, array $teamCompetitors ) {
        $table = new ConsoleTable();
        $table->setHeaders(array('league', 'season', 'batchNr', 'datetime', 'home', 'score', 'away' ) );

        $nameService = new NameService( new PlaceLocationMap( $teamCompetitors ) );

        foreach( $games as $game ) {
            $row = array(
                $competition->getLeague()->getName(),
                $competition->getSeason()->getName(),
                $game->getBatchNr(),
                $game->getStartDateTime()->format(\DateTime::ATOM),
                $nameService->getPlacesFromName($game->getPlaces(Game::HOME), true, true),
                $this->getScore($game),
                $nameService->getPlacesFromName($game->getPlaces(Game::AWAY), true, true),
            );
            $table->addRow($row);
        }
        $table->display();
    }

    protected function getScore(Game $game): string {
        return join( "&", $game->getScores()->map( function( Game\Score $gameScore ): string {
            return $gameScore->getHome() . " - " . $gameScore->getAway() ;
        })->toArray() );
    }
}
